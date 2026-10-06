document.addEventListener('DOMContentLoaded', function() {
    const setupHeaderScroll = () => {
    const scrollContainer = document.getElementById('header-categories-bar');
        const leftBtn = document.getElementById('header-scroll-left');
        const rightBtn = document.getElementById('header-scroll-right');
    const getScrollAmount = () => Math.max(240, Math.floor(scrollContainer.clientWidth * 0.85));

    if (!scrollContainer || !leftBtn || !rightBtn) {
        console.error('Elementos necesarios no encontrados');
        return;
    }

    function checkScroll() {
        const hasScroll = scrollContainer.scrollWidth > scrollContainer.clientWidth;
        leftBtn.style.display = hasScroll ? 'flex' : 'none';
        rightBtn.style.display = hasScroll ? 'flex' : 'none';
        if (hasScroll) updateScrollButtons();
    }

    function updateScrollButtons() {
        const scrollLeft = scrollContainer.scrollLeft;
        const maxScroll = scrollContainer.scrollWidth - scrollContainer.clientWidth;
        
        leftBtn.style.opacity = scrollLeft <= 0 ? '0.5' : '1';
        leftBtn.style.pointerEvents = scrollLeft <= 0 ? 'none' : 'auto';
        
        rightBtn.style.opacity = scrollLeft >= maxScroll ? '0.5' : '1';
        rightBtn.style.pointerEvents = scrollLeft >= maxScroll ? 'none' : 'auto';
    }

    leftBtn.addEventListener('click', function() {
        scrollContainer.scrollBy({
        left: -getScrollAmount(),
            behavior: 'smooth'
        });
    });

    rightBtn.addEventListener('click', function() {
        scrollContainer.scrollBy({
        left: getScrollAmount(),
            behavior: 'smooth'
        });
    });

    scrollContainer.addEventListener('scroll', updateScrollButtons);
    let isDown = false;
    let startX;
    let scrollLeft;

    scrollContainer.addEventListener('mousedown', function(e) {
        isDown = true;
        scrollContainer.style.cursor = 'grabbing';
        startX = e.pageX - scrollContainer.offsetLeft;
        scrollLeft = scrollContainer.scrollLeft;
    });

    scrollContainer.addEventListener('mouseleave', function() {
        isDown = false;
        scrollContainer.style.cursor = 'grab';
    });

    scrollContainer.addEventListener('mouseup', function() {
        isDown = false;
        scrollContainer.style.cursor = 'grab';
    });

    scrollContainer.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - scrollContainer.offsetLeft;
        const walk = (x - startX) * 2;
        scrollContainer.scrollLeft = scrollLeft - walk;
        updateScrollButtons();
    });

    setTimeout(checkScroll, 100);
    window.addEventListener('resize', checkScroll);
    };

    setTimeout(setupHeaderScroll, 500);
});
