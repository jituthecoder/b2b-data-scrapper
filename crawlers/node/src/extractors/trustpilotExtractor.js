const cheerio = require('cheerio');

function extractTrustpilotData(html) {
  if (!html) return null;

  const $ = cheerio.load(html);
  let email = null;
  let phone = null;
  let rating = null;
  let reviewCount = null;

  // 1. Try JSON-LD Schema parsing
  $('script[type="application/ld+json"]').each((_, el) => {
    try {
      const data = JSON.parse($(el).html() || '{}');
      if (data['@type'] === 'LocalBusiness' || data['@type'] === 'Organization') {
        if (data.email) email = data.email;
        if (data.telephone) phone = data.telephone;
        if (data.aggregateRating) {
          rating = data.aggregateRating.ratingValue;
          reviewCount = data.aggregateRating.reviewCount;
        }
      }
    } catch (e) {}
  });

  // 2. Fallback regex for phone & email in raw Trustpilot page text
  if (!email) {
    const emailMatch = html.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);
    if (emailMatch) email = emailMatch[0];
  }

  if (!phone) {
    const phoneMatch = html.match(/\+?[0-9]{1,4}[\s\-\.]?\(?[0-9]{2,4}\)?[\s\-\.]?[0-9]{3,4}[\s\-\.]?[0-9]{3,4}/);
    if (phoneMatch) phone = phoneMatch[0];
  }

  return {
    email: email,
    phone: phone,
    rating: rating,
    review_count: reviewCount,
  };
}

module.exports = extractTrustpilotData;
