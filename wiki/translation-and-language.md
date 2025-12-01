# Translation & Language

Our site serves both Norwegian and English readers. This page explains how translation works and how to keep names and titles correct. Everything below uses the normal WordPress editor—no extra plugins or coding needed.

## How translation works today
- We use **GTranslate** for automatic Norwegian↔English switching on the public site.
- No Polylang setup is required. Keep Polylang deactivated to avoid duplicate translation layers.
- Editors can write content in Norwegian; GTranslate handles the English view. You do **not** need to duplicate posts into English.

## Protecting people data
- Team member names, role titles, and quotes are marked `notranslate` in our templates.
- Avoid adding additional translation wrappers around names so they stay accurate in both languages.
- Keep partner names and program titles exactly as provided by the partner; avoid translating them manually.

## Adding manual translations (optional)
- All custom code uses standard WordPress localization functions with text domains `uv-core`, `uv-people`, and `uv-kadence-child`.
- If we later want manual strings, place `.po/.mo` files in each plugin’s `languages/` folder or use a different translation plugin. Volunteers do not need to manage these files.

## Editor tips
- Write clear Norwegian that is easy for machine translation; avoid slang in headings and buttons.
- Include alt text and headings as usual—these also get translated automatically.
- If a sentence looks odd in English, adjust the Norwegian phrasing instead of editing the machine output directly.
- For images with text, describe the meaning in the alt text so English readers understand it when translated.
