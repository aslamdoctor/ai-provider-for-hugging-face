# Contributing to AI Provider for Hugging Face

Thank you for your interest in contributing! Here's how to get started.

## Development Setup

1. Clone the repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/aslamdoctor/ai-provider-for-hugging-face.git
   ```

2. Install Node.js dependencies and build the connector JS:
   ```bash
   cd ai-provider-for-hugging-face
   npm install
   npm run build
   ```

3. Activate the plugin in WordPress admin.

4. Go to **Settings > Connectors** and enter your [Hugging Face API key](https://huggingface.co/settings/tokens).

## Development Workflow

- Run `npm run start` for development with watch mode.
- Run `npm run build` for a production build.

## Coding Guidelines

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).
- Maintain PHP 7.4+ compatibility.
- Add PHPDoc comments to all public methods.
- Use WordPress i18n functions for user-facing strings with the `ai-provider-for-hugging-face` text domain.

## Submitting Changes

1. Fork the repository and create a feature branch.
2. Make your changes following the coding guidelines above.
3. Test your changes on WordPress 7.0+.
4. Submit a pull request with a clear description of your changes.

## Reporting Issues

Please use the [GitHub issue tracker](https://github.com/aslamdoctor/ai-provider-for-hugging-face/issues) to report bugs or request features.
