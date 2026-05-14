# Inganzo Ngari — Coming Soon

A one-page coming-soon site for the Rwandan cultural troupe **Inganzo Ngari**.

> *"Where tradition meets the stage."*

The page features a cinematic dark hero, a live countdown to the launch date,
a trilingual toggle (English / Kinyarwanda / French), social media links and a
contact email — all built as a single static page so it can be hosted anywhere
with zero build step.

---

## Stack

- `index.html` — markup, SEO/OG tags, inline SVG logo
- `styles.css` — design tokens, Imigongo-inspired pattern, responsive layout
- `script.js` — countdown timer + language switcher (vanilla JS, no deps)

No build tools, no package manager, no frameworks.

## Configuration

| What | Where | How |
|------|-------|-----|
| Launch date | `script.js` → `LAUNCH_DATE` | Edit the ISO string (currently `2026-06-01T19:00:00+02:00`, Africa/Kigali) |
| Tagline / copy | `script.js` → `I18N` | Edit any of the three language dictionaries |
| Social links | `index.html` → `.socials` | Replace each `href="#"` |
| Contact email | `index.html` → `.contact` | Replace `info@inganzongari.com` |
| Colour palette | `styles.css` → `:root` | Tweak the CSS custom properties |

## Local preview

```bash
# Any static server works. Examples:
python3 -m http.server 8000
# then open http://localhost:8000
```

## Deploy

The site is three static files. Drop them on any host:

- **GitHub Pages** — push to `main` and enable Pages
- **Netlify / Vercel** — drag-and-drop or connect the repo (no build command)
- **Any web server** — copy the three files to the document root

## Browser support

Modern evergreen browsers (Chrome, Edge, Firefox, Safari). Uses
`Intl.DateTimeFormat`, `backdrop-filter` and CSS custom properties.
