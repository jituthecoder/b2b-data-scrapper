const cheerio = require('cheerio');

function extractCompanyLogo(html, baseUrl) {
  if (!html) return null;

  const $ = cheerio.load(html);
  let logoUrl = null;

  // 1. Try Schema.org JSON-LD parsing
  $('script[type="application/ld+json"]').each((_, el) => {
    try {
      const data = JSON.parse($(el).html() || '{}');
      if (data.logo) {
        logoUrl = typeof data.logo === 'string' ? data.logo : data.logo.url;
      } else if (data['@graph']) {
        for (const item of data['@graph']) {
          if (item.logo) {
            logoUrl = typeof item.logo === 'string' ? item.logo : item.logo.url;
            break;
          }
        }
      }
    } catch (e) {}
  });

  // 2. Try OpenGraph image (<meta property="og:image" content="...">)
  if (!logoUrl) {
    const ogImage = $('meta[property="og:image"]').attr('content') || $('meta[name="og:image"]').attr('content');
    if (ogImage) logoUrl = ogImage;
  }

  // 3. Try high-confidence <img> tags with class/alt/id containing 'logo'
  if (!logoUrl) {
    $('img').each((_, el) => {
      const src = $(el).attr('src');
      const alt = $(el).attr('alt') || '';
      const cls = $(el).attr('class') || '';
      const id = $(el).attr('id') || '';

      if (src && (alt.toLowerCase().includes('logo') || cls.toLowerCase().includes('logo') || id.toLowerCase().includes('logo'))) {
        logoUrl = src;
        return false;
      }
    });
  }

  // 4. Try favicon or apple-touch-icon
  if (!logoUrl) {
    const appleIcon = $('link[rel="apple-touch-icon"]').attr('href') || $('link[rel="icon"]').attr('href');
    if (appleIcon) logoUrl = appleIcon;
  }

  if (!logoUrl) return null;

  // Resolve relative URLs against baseUrl
  try {
    if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
      const base = new URL(baseUrl || 'https://example.com');
      logoUrl = new URL(logoUrl, base).href;
    }
  } catch (e) {}

  return logoUrl;
}

module.exports = extractCompanyLogo;
