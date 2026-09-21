# Localization

Supported locales are `en` and `am`. Public URLs are locale-prefixed, and middleware stores the selected locale in session. Interface copy uses Laravel catalogs; managed content uses locale-specific version records.

Adding a locale requires configuration, an enabled locale row, complete interface catalogs, content workflow support, search indexing, SEO alternates and regression tests. Missing translation is never treated as permission to publish fallback content.
