export const GLIDE_PX_PER_SECOND = 36;

// Keeps a horizontal scroll container gliding right to left for ever. The
// row is native scrolling, so it also swipes on touch screens and does not
// depend on hover state or compositing, which is where a CSS transform
// marquee fails on phones. The content must hold its set of cards `copies`
// times; once the scroll position passes one set it wraps back by exactly
// one set, which shows identical cards and is invisible. The glide pauses
// while a pointer is down, a mouse hovers, a card has keyboard focus, or
// the row is still settling after a swipe or wheel scroll. Returns a stop
// function.
export function glide(element, copies, speed = GLIDE_PX_PER_SECOND) {
    const state = { hovering: false, focused: false, touching: false, settling: false, driving: false };
    let position = element.scrollLeft;
    let previous = 0;
    let frame = 0;
    let settleTimer = 0;

    const rearm = () => {
        state.settling = true;
        window.clearTimeout(settleTimer);
        settleTimer = window.setTimeout(() => { state.settling = false; }, 250);
    };
    const tick = (now) => {
        const elapsed = previous ? Math.min(now - previous, 100) : 0;
        previous = now;
        const setWidth = element.scrollWidth / copies;
        const canDrive = ! state.hovering && ! state.focused && ! state.touching && ! state.settling
            && setWidth > 0 && element.scrollWidth > element.clientWidth;
        if (canDrive) {
            // Pick up from wherever the visitor left the row.
            if (! state.driving) {
                position = element.scrollLeft % setWidth;
            }
            position = (position + speed * elapsed / 1000) % setWidth;
            element.scrollLeft = position;
        }
        state.driving = canDrive;
        frame = window.requestAnimationFrame(tick);
    };

    const onPointerEnter = (event) => { if (event.pointerType === 'mouse') state.hovering = true; };
    const onPointerLeave = (event) => { if (event.pointerType === 'mouse') state.hovering = false; };
    const onPointerDown = () => { state.touching = true; };
    const onPointerUp = () => { if (state.touching) { state.touching = false; rearm(); } };
    const onWheel = () => rearm();
    const onScroll = () => { if (state.settling) rearm(); };
    const onFocusIn = (event) => { state.focused = event.target.matches(':focus-visible'); };
    const onFocusOut = () => { state.focused = false; };

    element.addEventListener('pointerenter', onPointerEnter);
    element.addEventListener('pointerleave', onPointerLeave);
    element.addEventListener('pointerdown', onPointerDown);
    window.addEventListener('pointerup', onPointerUp);
    window.addEventListener('pointercancel', onPointerUp);
    element.addEventListener('wheel', onWheel, { passive: true });
    element.addEventListener('scroll', onScroll, { passive: true });
    element.addEventListener('focusin', onFocusIn);
    element.addEventListener('focusout', onFocusOut);
    frame = window.requestAnimationFrame(tick);

    return () => {
        window.cancelAnimationFrame(frame);
        window.clearTimeout(settleTimer);
        element.removeEventListener('pointerenter', onPointerEnter);
        element.removeEventListener('pointerleave', onPointerLeave);
        element.removeEventListener('pointerdown', onPointerDown);
        window.removeEventListener('pointerup', onPointerUp);
        window.removeEventListener('pointercancel', onPointerUp);
        element.removeEventListener('wheel', onWheel);
        element.removeEventListener('scroll', onScroll);
        element.removeEventListener('focusin', onFocusIn);
        element.removeEventListener('focusout', onFocusOut);
    };
}
