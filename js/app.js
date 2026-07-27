document.addEventListener('DOMContentLoaded', function() {
    if (window.matchMedia('(hover: hover)').matches) {
        var cursor = document.createElement('div');
        cursor.id = 'cursor';
        document.body.appendChild(cursor);

        var cursorX = 0, cursorY = 0;
        var targetX = 0, targetY = 0;

        document.addEventListener('mousemove', function(e) {
            targetX = e.clientX;
            targetY = e.clientY;
        });

        function animate() {
            cursorX += (targetX - cursorX) * 0.15;
            cursorY += (targetY - cursorY) * 0.15;
            cursor.style.left = cursorX - 7 + 'px';
            cursor.style.top = cursorY - 7 + 'px';
            requestAnimationFrame(animate);
        }
        animate();

        document.addEventListener('mousedown', function() { cursor.classList.add('clicking'); });
        document.addEventListener('mouseup', function() { cursor.classList.remove('clicking'); });

        document.querySelectorAll('a, button, .nb-btn, .nb-tag, input, select, textarea').forEach(function(el) {
            el.style.cursor = 'none';
        });
    }
});
