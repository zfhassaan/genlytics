# Genlytics Website & Documentation

This directory contains the Vue 3 documentation and showcase website for **`zfhassaan/genlytics`**.

## Directory Structure

```
website/
├── src/
│   └── content/            # Markdown documentation pages
│       ├── getting-started.md
│       ├── architecture.md
│       ├── configuration.md
│       ├── api-reference.md
│       ├── batch-reporting.md
│       └── faq.md
├── index.html              # Vue 3 template & layout
├── style.css               # Modern responsive styling with Dark/Light mode
├── app.js                  # Vue 3 reactive app and query generator
├── package.json            # Scripts & metadata
└── build.js                # Compiler script that builds into ../docs/
```

## Building the Documentation

To build the static website into the [`docs/`](../docs/) directory (ready for GitHub Pages):

```bash
npm run build
# or
node build.js
```

## Features

- **Vue 3 Reactive SPA**: Zero-build runtime with instant navigation.
- **Interactive GA4 Query Builder**: Select dimensions, metrics, and date ranges to generate copy-pasteable Laravel PHP code.
- **Dark / Light Mode**: Automatically detects system preference and persists in `localStorage`.
- **Searchable Documentation**: Instant client-side search across all markdown guides.
- **GitHub Pages Ready**: Outputs to `docs/` with `.nojekyll` configuration.

