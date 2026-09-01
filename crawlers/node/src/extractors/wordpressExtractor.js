const cheerio = require('cheerio');

const KNOWN_PLUGINS = {
  'yoast-seo': 'Yoast SEO',
  'wordpress-seo': 'Yoast SEO',
  'woocommerce': 'WooCommerce',
  'js_composer': 'WPBakery Page Builder',
  'elementor': 'Elementor',
  'elementor-pro': 'Elementor Pro',
  'gdpr-cookie-compliance': 'GDPR Cookie Compliance',
  'contact-form-7': 'Contact Form 7',
  'w3speedster': 'W3 Speedster',
  'w3-total-cache': 'W3 Total Cache',
  'wp-rocket': 'WP Rocket',
  'wordfence': 'Wordfence Security',
  'revslider': 'Slider Revolution',
  'jetpack': 'Jetpack',
  'all-in-one-seo-pack': 'All in One SEO',
  'wpforms-lite': 'WPForms',
  'wpforms': 'WPForms',
  'gravityforms': 'Gravity Forms',
  'mailchimp-for-wp': 'Mailchimp for WordPress',
  'updraftplus': 'UpdraftPlus Backup',
  'really-simple-ssl': 'Really Simple SSL',
  'wp-super-cache': 'WP Super Cache',
  'autoptimize': 'Autoptimize',
  'litespeed-cache': 'LiteSpeed Cache',
  'classic-editor': 'Classic Editor',
  'gutenberg': 'Gutenberg Editor',
  'understrap': 'Understrap Framework',
};

function formatSlugToTitle(slug) {
  if (KNOWN_PLUGINS[slug.toLowerCase()]) {
    return KNOWN_PLUGINS[slug.toLowerCase()];
  }
  return slug
    .replace(/[-_]+/g, ' ')
    .replace(/\b\w/g, char => char.toUpperCase());
}

function extractWordPressDetails(html) {
  if (!html) return [];

  const technologies = [];
  const foundThemes = new Set();
  const foundPlugins = new Set();

  // 1. Detect WordPress Themes from /wp-content/themes/{theme_name}/
  const themeRegex = /\/wp-content\/themes\/([a-zA-Z0-9_-]+)\//gi;
  let match;
  while ((match = themeRegex.exec(html)) !== null) {
    const slug = match[1].toLowerCase();
    if (slug && !['inc', 'assets', 'css', 'js', 'images'].includes(slug)) {
      foundThemes.add(slug);
    }
  }

  // 2. Detect WordPress Plugins from /wp-content/plugins/{plugin_name}/
  const pluginRegex = /\/wp-content\/plugins\/([a-zA-Z0-9_-]+)\//gi;
  while ((match = pluginRegex.exec(html)) !== null) {
    const slug = match[1].toLowerCase();
    if (slug && !['inc', 'assets', 'css', 'js', 'images', 'vendor'].includes(slug)) {
      foundPlugins.add(slug);
    }
  }

  // Format Theme Technologies
  for (const themeSlug of foundThemes) {
    technologies.push({
      name: formatSlugToTitle(themeSlug),
      category: 'WordPress Theme',
      confidence_score: 1.0,
    });
  }

  // Format Plugin Technologies
  for (const pluginSlug of foundPlugins) {
    technologies.push({
      name: formatSlugToTitle(pluginSlug),
      category: 'WordPress Plugin',
      confidence_score: 1.0,
    });
  }

  return technologies;
}

module.exports = extractWordPressDetails;
