document.addEventListener('DOMContentLoaded', function() {
    const breadcrumbs = document.querySelector('.breadcrumbs');
    if (!breadcrumbs) return;

    // RTL detection
    const isRTL = document.dir === 'rtl' || document.documentElement.dir === 'rtl' || document.body.classList.contains('rtl');

    // Auto-scroll to end (active item) on load
    setTimeout(() => {
        const scrollEnd = isRTL ? 0 : breadcrumbs.scrollWidth;
        // For RTL, scrollLeft is usually negative or 0 depending on browser,
        // but often we want to see the last item which is physically on the left in RTL?
        // Wait, standard breadcrumbs: Home > Category > Project.
        // In LTR: Home (left) ... Project (right). We want to see Project. So scrollWidth.
        // In RTL: Home (right) ... Project (left).
        // Most browsers handle RTL scrollLeft differently.
        // Chrome: 0 is rightmost, negative goes left.
        // Firefox/IE might differ.
        // Safest is to use scrollTo.

        if (isRTL) {
             // Try to scroll to the "end" which is the leftmost point
             breadcrumbs.scrollLeft = -breadcrumbs.scrollWidth;
             // Also try alternative for different browsers
             if (breadcrumbs.scrollLeft === 0) {
                 breadcrumbs.scrollLeft = 0; // If 0 is leftmost (older spec) - wait, usually 0 is starting point (Right).
                 // To go to the end (Left), we need a large negative number or large positive depending on implementation.
                 // Let's rely on scrollIntoView of the last element if possible.
                 const lastItem = breadcrumbs.lastElementChild;
                 if (lastItem) {
                     lastItem.scrollIntoView({ block: 'nearest', inline: 'end' });
                 }
             }
        } else {
            breadcrumbs.scrollLeft = breadcrumbs.scrollWidth;
        }
    }, 100);

    let scrollSpeed = 0;
    let isScrolling = false;

    breadcrumbs.addEventListener('mousemove', function(e) {
        const rect = breadcrumbs.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const width = rect.width;

        // Settings
        const zoneWidth = 100; // px
        const maxSpeed = 8;

        // Reset speed
        scrollSpeed = 0;

        if (x < zoneWidth) {
            // Mouse is on the Left side
            // In LTR: Scroll Left (decrease scrollLeft)
            // In RTL: Scroll Right (visually) -> increase/decrease scrollLeft depending on browser?
            // Let's think logically:
            // Left Zone: We want to move the view towards the left.
            // LTR: view moves left = scrollLeft decreases.
            // RTL: view moves left = scrollLeft decreases (becomes more negative) or increases (if 0 is left).

            // Let's simplify:
            // Left Zone -> Move View Left.
            // Right Zone -> Move View Right.

            const intensity = 1 - (x / zoneWidth);
            scrollSpeed = - (maxSpeed * intensity); // Negative value

        } else if (x > width - zoneWidth) {
            // Mouse is on the Right side
            const intensity = 1 - ((width - x) / zoneWidth);
            scrollSpeed = (maxSpeed * intensity); // Positive value
        }

        if (scrollSpeed !== 0) {
            if (!isScrolling) {
                isScrolling = true;
                animateScroll();
            }
        } else {
            isScrolling = false;
        }
    });

    breadcrumbs.addEventListener('mouseleave', function() {
        isScrolling = false;
        scrollSpeed = 0;
    });

    function animateScroll() {
        if (!isScrolling) return;

        // Determine direction based on Scroll Speed (+ goes Right, - goes Left)
        // This works for LTR naturally.
        // For RTL:
        // If 0 is Rightmost:
        //   - Scroll Left (visual) requires scrollLeft to become negative (Chrome) or positive (some browsers).
        // Let's assume standard behavior where 'scrollLeft += value' moves view right in LTR.
        // In RTL, usually we want to invert the logic or just trust the browser's directionality?
        // Actually, 'scrollLeft += positive' usually moves view towards the 'end' of the track in physical pixels?
        // No, scrollLeft is logical.

        // Let's just try adding the speed.
        // If LTR: speed is positive (Right zone) -> scrollLeft increases -> View moves Right (showing content on the right). Correct.
        // If RTL: speed is positive (Right zone) -> scrollLeft increases.
        //   If 0 is Right: scrollLeft increasing might do nothing (max is 0).
        //   If we want to move view Right (towards start), we usually need to increase scrollLeft (towards 0).
        //   If we want to move view Left (towards end), we need to decrease scrollLeft.

        // So:
        // Left Zone (speed negative): Move View Left.
        // Right Zone (speed positive): Move View Right.

        breadcrumbs.scrollLeft += scrollSpeed;

        requestAnimationFrame(animateScroll);
    }
});
