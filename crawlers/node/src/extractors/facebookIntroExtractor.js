const cheerio = require('cheerio');

function extractFacebookIntro(html) {
  if (!html) return null;

  const $ = cheerio.load(html);
  let email = null;
  let phone = null;
  let category = null;
  let website = null;

  // 1. Scan for emails matching business domain or standard pattern
  const emailMatch = html.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);
  if (emailMatch) {
    email = emailMatch[0];
  }

  // 2. Scan for US / International phone numbers (e.g. +1 213-600-2456)
  const phoneMatch = html.match(/\+?1?[\s\-\.]?\(?\d{3}\)?[\s\-\.]?\d{3}[\s\-\.]?\d{4}/);
  if (phoneMatch) {
    phone = phoneMatch[0];
  }

  // 3. Scan for Page category (e.g. Page · Information technology company)
  const categoryMatch = html.match(/Page\s*·\s*([a-zA-Z0-9\s&,\-_]+)/i);
  if (categoryMatch) {
    category = categoryMatch[1].trim();
  }

  return {
    email: email,
    phone: phone,
    category: category,
  };
}

module.exports = extractFacebookIntro;
