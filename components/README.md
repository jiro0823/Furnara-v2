# Reusable components

Files here are PHP markup fragments, not standalone pages. They should not output document-level `<html>`, `<head>`, or `<body>` tags.

## Shared navigation and page chrome

- `navigation/storefront_navbar.php` renders the public navigation and adapts its active link to the current public page.
- `navigation/admin_sidebar.php` and `navigation/user_sidebar.php` render admin and customer account navigation.
- `site_footer.php` renders the shared contact, quick links, and copyright footer.

## Home page sections

`user.php` and `Landingpage.php` call these same section files so their shared content stays consistent:

- `home/dream_hero.php` — `#home-dream-hero`
- `home/timeless_furniture.php` — `#timeless-furniture-showcase`
- `home/brand_story.php` — `#brand-story`
- `home/featured_collections.php` — `#featured-collections`
- `home/comfort_style.php` — `#comfort-and-style`
- `home/customer_testimonials.php` — `#customer-testimonials`

The home pages keep their distinct sustainability/perspectives copy in the page files. Include a component from a page with:

```php
<?php require dirname(__DIR__, 2) . '/components/path/to/component.php'; ?>
```

Give each new page section a descriptive `id` and connect its heading with `aria-labelledby` when it has one. The `id` is also the quickest way to find the section in the editor.