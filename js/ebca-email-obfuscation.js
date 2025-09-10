/**
 * EBCA Email Obfuscation
 *
 * Converts HTML elements with 'ebca-eml' class and 'data-eml' attribute
 * to proper email elements with obfuscation protection.
 *
 * Examples:
 *
 * <a href="#" class="ebca-eml" rel="nofollow, noindex" data-eml="https://domain.com/user">Email me</a>
 *
 * <span class="ebca-eml underline" data-eml="https://example.com/sales"></span>
 *
 * <a href="#" class="ebca-eml underline" rel="nofollow, noindex" data-eml="https://example.com/support"></a>
 *
 * <a class="ebca-eml" data-eml="https://example.com/marketing">Get in touch with Marketing!</a>
 *
 * <a href="#"
 *    class="ebca-eml underline text-lg"
 *    rel="nofollow, noindex"
 *    target="_blank"
 *    data-eml="https://example.com/help">
 *     Email support
 * </a>
 *
 * @since 1.0.11
 */
(function () {
    'use strict';

    /**
     * Extract email address from URL
     *
     * @param {string} url - The URL containing the email domain and path
     * @return {string} - The constructed email address
     */
    function extractEmailFromUrl(url) {
        try {
            const urlObj = new URL(url);
            const domain = urlObj.hostname;
            let username = urlObj.pathname.replace(/^\//, ''); // Remove leading slash

            // If no path provided, use 'foo' as default
            if (!username) {
                username = 'foo';
            }

            return username + '@' + domain;
        } catch (error) {
            console.error('EBCA Email Obfuscation: Invalid URL format', error);
            return '';
        }
    }

    /**
     * Process email obfuscation elements
     */
    function processEmailElements() {

        // Find all elements with 'ebca-eml' class
        const elements = document.querySelectorAll('.ebca-eml');

        elements.forEach(function (element) {

            const url = element.getAttribute('data-eml');

            if (!url) {
                console.warn('EBCA Email Obfuscation: Element found with ebca-eml class but no data-eml attribute');
                return;
            }

            const emailAddress = extractEmailFromUrl(url);

            if (!emailAddress) {
                console.warn('EBCA Email Obfuscation: Could not extract email from URL:', url);
                return;
            }

            // Remove the ebca-eml class
            element.classList.remove('ebca-eml');

            // Remove the data-eml attribute if it exists
            if (element.hasAttribute('data-eml')) {
                element.removeAttribute('data-eml');
            }

            // Handle different element types
            if (element.tagName.toLowerCase() === 'span') {
                // For span elements, just set the text content
                element.textContent = emailAddress;
            } else if (element.tagName.toLowerCase() === 'a') {
                // For anchor elements, set href to mailto and text content if empty
                element.href = 'mailto:' + emailAddress;

                // Check if the anchor has meaningful content (text or HTML elements)
                // Only set text content if the element is truly empty (no text and no HTML elements)
                const hasTextContent = element.textContent.trim().length > 0;
                const hasHtmlElements = element.children.length > 0;
                
                if (!hasTextContent && !hasHtmlElements) {
                    element.textContent = emailAddress;
                }
                // If it has HTML elements (like SVG) or text content, preserve it
            } else {
                // For other elements, just set the text content
                element.textContent = emailAddress;
            }
        });
    }

    /**
     * Initialize email obfuscation when DOM is ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', processEmailElements);
        } else {
            // DOM is already loaded
            processEmailElements();
        }
    }

    // Initialize the script
    init();

})();