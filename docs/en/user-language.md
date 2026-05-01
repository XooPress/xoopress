# Language Switching

XooPress supports multiple languages for both the admin interface and public-facing content.

## Changing the Site Language

Use the language switcher dropdown in the site header to change the display language. The available options are:

- **English** (default)
- **Deutsch** (German)
- **Français** (French)

## How It Works

1. Select a language from the dropdown
2. The page reloads with the new language
3. Your preference is stored in the session
4. All subsequent pages will use the selected language

## Content Language

When creating posts, you can assign a language to each post. This allows you to:

- Create content in multiple languages
- Filter posts by language on the front-end
- Build multilingual sites

## Adding New Languages

To add a new language:

1. Add the locale to `config/app.php`:
   ```php
   'i18n' => [
       'available_locales' => ['en_US', 'de_DE', 'fr_FR', 'es_ES'],
   ],
   ```
2. Create the locale directory:
   ```bash
   mkdir -p locales/es_ES/LC_MESSAGES/
   ```
3. Create a `.mo` translation file or use gettext
4. The language will appear in the switcher automatically