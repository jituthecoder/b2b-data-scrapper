const cheerio = require('cheerio');

const SOCIAL_PATTERNS = [
  { platform: 'linkedin', regex: /https?:\/\/(www\.|[a-z]{2}\.)?linkedin\.com\/(company|in|pub|school)\/[a-zA-Z0-9%._-]+/gi },
  { platform: 'facebook', regex: /https?:\/\/(www\.)?facebook\.com\/(pages\/[a-zA-Z0-9_.-]+\/[0-9]+|[a-zA-Z0-9%._-]+)/gi },
  { platform: 'twitter', regex: /https?:\/\/(www\.)?(twitter|x)\.com\/[a-zA-Z0-9_]+/gi },
  { platform: 'instagram', regex: /https?:\/\/(www\.)?instagram\.com\/[a-zA-Z0-9_.-]+/gi },
  { platform: 'github', regex: /https?:\/\/(www\.)?github\.com\/[a-zA-Z0-9_-]+/gi },
  { platform: 'youtube', regex: /https?:\/\/(www\.)?youtube\.com\/(user|channel|c|@)?[a-zA-Z0-9_%.-]+/gi },
  { platform: 'pinterest', regex: /https?:\/\/(www\.)?pinterest\.com\/[a-zA-Z0-9_-]+/gi },
];

function extractSocialProfiles(html) {
  if (!html) return [];

  const socialUrls = new Set();

  // Clean escaped slashes in JSON-LD (e.g. https:\/\/www.facebook.com\/w3speeedup -> https://www.facebook.com/w3speeedup)
  const unescapedHtml = html.replace(/\\r|\\n/g, '').replace(/\\{1,2}\//g, '/');

  // 1. Scan entire unescaped HTML string for all social regex patterns
  for (const item of SOCIAL_PATTERNS) {
    const matches = unescapedHtml.match(item.regex) || [];
    for (const match of matches) {
      const cleanUrl = match.trim().replace(/\/+$/, '');
      if (!cleanUrl.includes('/sharer') && !cleanUrl.includes('/share') && !cleanUrl.includes('/intent') && !cleanUrl.includes('/legal')) {
        socialUrls.add(cleanUrl);
      }
    }
  }

  // 2. DOM Check on <a> and <meta> tags
  try {
    const $ = cheerio.load(html);

    $('a[href], meta[content]').each((_, el) => {
      const val = $(el).attr('href') || $(el).attr('content');
      if (!val) return;

      for (const item of SOCIAL_PATTERNS) {
        item.regex.lastIndex = 0;
        if (item.regex.test(val)) {
          const cleanVal = val.trim().replace(/\\/g, '').replace(/\/+$/, '');
          if (!cleanVal.includes('/sharer') && !cleanVal.includes('/share') && !cleanVal.includes('/intent') && !cleanVal.includes('/legal')) {
            socialUrls.add(cleanVal);
          }
        }
      }
    });
  } catch (err) {
    // Cheerio parse safety
  }

  return Array.from(socialUrls);
}

module.exports = extractSocialProfiles;
