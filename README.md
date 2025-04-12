# snakeShaker 🐍

**Don't get bitten by risky Python packages!**

snakeShaker is a simple web utility designed to give you a quick first glance at the potential risks associated with package imports in your Python code snippets. Paste your code, give it a shake, and see what slithers out! It primarily focuses on Python (PyPI) but has basic awareness of JavaScript (npm) and PHP (Packagist) imports too.

**Check it out live: [https://snake.timnetworks.net](https://snake.timnetworks.net)** 🐍

[![Screenshot of snakeShaker interface](https://raw.githubusercontent.com/timnetworks/snakeShaker/refs/heads/main/screenshot.jpg)](https://snake.timnetworks.net)

---

## What's This Hiss About? (Features)

*   **Identifies Imports:** Uses regular expressions to find `import` and `from ... import` statements in Python code, plus basic `require`/`import` in JS and `use` statements in PHP.
*   **Registry Checks:** Queries public package registries (PyPI, npm Registry, Packagist) and the wise old Libraries.io API to verify package existence.
*   **Basic Metadata:** Fetches information like the package author/maintainers ("handlers") and the date the package first slithered online ("First Seen").
*   **Heuristic Safety Assessment:** Provides a *very basic*, *heuristic* (read: educated guess!) safety likelihood based primarily on the package's age and registry status. Think of it as trying to tell a harmless garter snake from a potential viper based on first impressions – *not* a definitive identification!
*   **Built-in Awareness:** Recognizes common Python and Node.js built-in modules (no need to fear the `os` or `fs` modules!).
*   **Simple Interface:** Just paste, click, and view the results in a clear table.

## How to Use

1.  Navigate to the lair: [https://snake.timnetworks.net](https://snake.timnetworks.net)
2.  Paste your Python (or JS/PHP) code snippet into the text area.
3.  Hit the **"shake it before you play with it"** button.
4.  Watch it hiss (analyze) for a moment.
5.  Examine the "Snakes in the Grass" results table for insights on your imports.

## Tech Stack / Ingredients

*   **Frontend:** Vanilla JavaScript (ES6+) for DOM manipulation and API interaction.
*   **Backend:** PHP for processing the code, orchestrating API calls, and basic safety heuristics.
*   **Styling:** CSS with a Solarized Light-inspired theme.
*   **APIs Used:**
    *   PyPI JSON API
    *   npm Registry API
    *   Packagist API
    *   Libraries.io API (Requires API Key - see `api.php`)

## Important Hisses (Limitations & Disclaimer)

🐍 **Anti-Venom Not Included! Please Read Carefully!** 🐍

*   **NOT a Security Scanner:** This tool is **NOT** a comprehensive security analysis tool. It uses relatively simple pattern matching (regex) and basic checks (like package age). It's intended as a *preliminary check* or a *curiosity tool*, not a guarantee of security or correctness.
*   **False Positives/Negatives:** Complex code structures, aliased imports (`import pandas as pd`), dynamic imports/requires, or unconventional formatting might confuse the parser, leading to missed packages or incorrect identification.
*   **Safety is Heuristic:** The "Safety Likelihood" is purely based on easily obtainable data like **creation date** and **existence** on the registry. **Age does NOT equal safety.** Malicious packages can exist for years, and brand new packages can be perfectly safe. This assessment is just *one* data point and should be taken with a large grain of salt.
*   **API Reliance:** The accuracy and completeness of the results depend entirely on the availability and data provided by the external PyPI, npm, Packagist, and Libraries.io APIs. Downtime or rate limiting on their end will affect results.
*   **Use Your Brain:** **Always** perform your own due diligence. Review the source code of dependencies, check for known vulnerabilities (using dedicated tools like `pip-audit`, `npm audit`, etc.), understand the package's reputation, and consider the context before trusting any third-party code.

## Contributing

Found a bug or have an idea for improvement? Feel free to:
*   Open an issue on the [GitHub Issues page](https://github.com/timnetworks/snakeShaker/issues).
*   Fork the repository and submit a Pull Request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details (or add one if you haven't!).

---

Happy (and safe) coding! Don't let the snakes bite!
