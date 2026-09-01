const cheerio = require('cheerio');
const { URL } = require('url');

const STRICT_PATTERNS = [
  { page_type: 'contact', regex: /\b(contact-us|contact|reach-us|get-in-touch|support-policy)\b/i },
  { page_type: 'careers', regex: /\b(careers?|jobs?|openings|work-with-us|join-us)\b/i },
  { page_type: 'about', regex: /\b(about-us|about|our-story|who-we-are)\b/i },
  { page_type: 'team', regex: /\b(team|leadership|management|founders)\b/i },
];

function deriveTitleFromUrl(urlPath, linkText) {
  const cleanText = (linkText || '').replace(/\s+/g, ' ').trim();
  const genericTexts = ['click here', 'read more', 'view details', 'learn more', 'n/a', 'link', 'more', 'view case study'];

  if (cleanText && !genericTexts.includes(cleanText.toLowerCase()) && cleanText.length > 2 && cleanText.length < 80) {
    return cleanText;
  }

  const segments = urlPath.split('/').filter(Boolean);
  if (segments.length > 0) {
    const lastSeg = segments[segments.length - 1].replace(/[-_]+/g, ' ');
    return lastSeg.replace(/\b\w/g, c => c.toUpperCase());
  }

  return null;
}

function extractDiscoveredPages(html, baseUrl) {
  if (!html || !baseUrl) return [];

  const $ = cheerio.load(html);
  const discoveredPages = new Map();

  // 1. Add Homepage (strip trailing slash)
  const homepageTitle = $('title').first().text().trim() || 'Homepage';
  const cleanBaseUrl = baseUrl.replace(/\/+$/, '');
  discoveredPages.set(cleanBaseUrl, {
    url: cleanBaseUrl,
    page_type: 'homepage',
    title: homepageTitle,
  });

  // 2. Iterate internal links strictly for allowed page types (contact, about, careers, team)
  $('a[href]').each((_, el) => {
    const href = $(el).attr('href');
    const text = $(el).text().trim();

    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
      return;
    }

    try {
      const parsedUrl = new URL(href, baseUrl);
      parsedUrl.hash = ''; // Remove #hash fragment completely
      let absoluteUrl = parsedUrl.toString();

      // Normalize trailing slash universally
      if (absoluteUrl.endsWith('/') && absoluteUrl.length > 8) {
        absoluteUrl = absoluteUrl.slice(0, -1);
      }

      const baseHost = new URL(baseUrl).hostname.replace(/^www\./i, '');
      const linkHost = parsedUrl.hostname.replace(/^www\./i, '');

      if (baseHost !== linkHost) return;

      const pathName = parsedUrl.pathname.toLowerCase();

      // Exclude services, case studies, iso certs, blog, legal explicitly
      if (/\/(services|service|case-study|case-studies|iso-|portfolio|blog|news|terms|privacy|legal)\b/i.test(pathName)) {
        return;
      }

      for (const pattern of STRICT_PATTERNS) {
        if (pattern.regex.test(pathName)) {
          if (!discoveredPages.has(absoluteUrl)) {
            const pageTitle = deriveTitleFromUrl(parsedUrl.pathname, text);
            discoveredPages.set(absoluteUrl, {
              url: absoluteUrl,
              page_type: pattern.page_type,
              title: pageTitle,
            });
          }
          break;
        }
      }
    } catch (err) {
      // Invalid URL format
    }
  });

  return Array.from(discoveredPages.values());
}

module.exports = extractDiscoveredPages;
