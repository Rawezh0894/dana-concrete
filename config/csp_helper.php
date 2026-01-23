<?php
if (!isset($_SESSION)) {
    session_start();
}

/**
 * Generate a cryptographically secure nonce for Content Security Policy.
 * The nonce should be different for every request.
 */
if (!isset($csp_nonce)) {
    $csp_nonce = base64_encode(random_bytes(16));
}

/**
 * Function to output the CSP header.
 * You can adjust the policy as needed.
 */
function sendCSPHeader($nonce) {
    $policy = "default-src 'none'; ";
    // Scripts: Remove unsafe-inline, use nonce for allowed sources
    $policy .= "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com 'nonce-$nonce'; ";
    // Styles: Keep self and common CDNs (Note: inline styles are often needed for dynamic UI components)
    $policy .= "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; ";
    // Fonts and images
    $policy .= "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; ";
    $policy .= "img-src 'self' data: https:; ";
    // Connection and frame protection
    $policy .= "connect-src 'self'; ";
    $policy .= "media-src 'self'; ";
    $policy .= "frame-src 'self'; ";
    $policy .= "frame-ancestors 'self'; ";
    $policy .= "base-uri 'self'; ";
    $policy .= "form-action 'self';";

    header("Content-Security-Policy: $policy");
}
