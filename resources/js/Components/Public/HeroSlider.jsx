import { usePage } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { useCallback, useEffect, useState } from 'react';
import { format, pad } from '../../lib/format';
import Icon from '../Icon';

const EASE = [0.2, 0.8, 0.2, 1];

export default function HeroSlider({ slider, stats = [], statsLabel }) {
    const { ui } = usePage().props;
    const slides = slider.slides;
    const count = slides.length;
    const reduceMotion = useReducedMotion();
    const [current, setCurrent] = useState(0);
    const [direction, setDirection] = useState(1);
    const [manuallyPaused, setManuallyPaused] = useState(false);
    const [hoverPaused, setHoverPaused] = useState(false);
    const [focusPaused, setFocusPaused] = useState(false);
    const autoplay = slider.autoplay && ! reduceMotion && count > 1;
    const paused = manuallyPaused || hoverPaused || focusPaused;

    const go = useCallback((index, dir) => {
        setDirection(dir);
        setCurrent((index + count) % count);
    }, [count]);

    useEffect(() => {
        if (! autoplay || paused) {
            return undefined;
        }

        const timer = window.setTimeout(() => go(current + 1, 1), Math.max(4000, slider.interval_ms ?? 7000));

        return () => window.clearTimeout(timer);
    }, [autoplay, paused, current, go, slider.interval_ms]);

    const slide = slides[current];
    const Heading = current === 0 ? 'h1' : 'h2';

    return (
        <section
            className="home-signature-hero-section home-hero-slider"
            aria-label={slider.label}
            aria-roledescription="carousel"
            onMouseEnter={() => slider.pause_on_hover && setHoverPaused(true)}
            onMouseLeave={() => setHoverPaused(false)}
            onFocus={() => setFocusPaused(true)}
            onBlur={(event) => ! event.currentTarget.contains(event.relatedTarget) && setFocusPaused(false)}
        >
            <div className="home-hero-slider-stage content-container">
                <AnimatePresence mode="wait" custom={direction} initial={false}>
                    <motion.article
                        key={current}
                        className="home-hero-slide"
                        role="group"
                        aria-roledescription={ui.slide}
                        aria-label={format(ui.slideOf, { current: current + 1, total: count })}
                        custom={direction}
                        initial={false}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.55, ease: EASE }}
                    >
                        <div className="home-signature-hero">
                            <div className="home-signature-copy">
                                <p className="insight-marker">
                                    <Icon name="insights" className="size-5" />
                                    {slide.eyebrow}
                                </p>
                                <Heading className="home-signature-title">{slide.heading}</Heading>
                                <p className="home-signature-summary">{slide.summary}</p>
                                {(slider.primary_action || slider.secondary_action) && (
                                    <div className="home-signature-actions">
                                        {slider.primary_action && (
                                            <a className="button-primary" href={slider.primary_action.href}>
                                                {slider.primary_action.label}
                                                <Icon name="arrow-right" className="size-4" strokeWidth={2} aria-hidden="true" />
                                            </a>
                                        )}
                                        {slider.secondary_action && (
                                            <a className="home-signature-text-link" href={slider.secondary_action.href}>
                                                {slider.secondary_action.label}
                                                <Icon name="arrow-up-right" className="size-4" />
                                            </a>
                                        )}
                                    </div>
                                )}
                            </div>
                            {stats.length > 0 && (
                                <div className="home-hero-evidence" role="group" aria-label={statsLabel}>
                                    {stats.map((item) => (
                                        <a className="home-hero-stat" key={item.label} href={item.href}>
                                            <strong>{item.value}</strong>
                                            <span>{item.label}</span>
                                        </a>
                                    ))}
                                </div>
                            )}
                            <motion.div
                                className="home-signature-art"
                                initial={{ opacity: 0, scale: 0.985 }}
                                animate={{ opacity: 1, scale: 1 }}
                                transition={{ duration: 0.8, ease: EASE, delay: 0.1 }}
                            >
                                <div className="home-signature-image">
                                    <img
                                        src={slide.image.includes('impact-hero-clean.svg') ? '/images/ethiopia-highlands.jpg' : slide.image}
                                        alt=""
                                        aria-hidden="true"
                                        width="1536"
                                        height="1024"
                                        fetchPriority={current === 0 ? 'high' : 'auto'}
                                        decoding="async"
                                    />
                                </div>
                                <div className="home-signature-caption" aria-hidden="true">
                                    <span>{slide.eyebrow}</span>
                                    <span className="home-signature-caption-index">{pad(current + 1)} / {pad(count)}</span>
                                </div>
                            </motion.div>
                        </div>
                    </motion.article>
                </AnimatePresence>
            </div>

            {count > 1 && (
                <div className="sr-only focus-within:not-sr-only">
                    <div className="home-hero-slider-controls">
                        <div className="home-hero-slider-dots" role="group" aria-label={ui.chooseSlide}>
                            {slides.map((item, index) => (
                                <button
                                    key={item.slot ?? index}
                                    type="button"
                                    className={index === current ? 'home-hero-slider-dot-active' : ''}
                                    aria-current={index === current ? 'true' : undefined}
                                    aria-label={format(ui.slideOf, { current: index + 1, total: count })}
                                    onClick={() => go(index, index > current ? 1 : -1)}
                                >
                                    <span>{pad(index + 1)}</span>
                                </button>
                            ))}
                        </div>
                        <div className="home-hero-slider-buttons">
                            <button type="button" onClick={() => go(current - 1, -1)} aria-label={ui.previousSlide}>
                                <Icon name="arrow-left" className="size-4" strokeWidth={2} />
                            </button>
                            {autoplay && (
                                <button
                                    type="button"
                                    onClick={() => setManuallyPaused((value) => ! value)}
                                    aria-label={manuallyPaused ? ui.playSlides : ui.pauseSlides}
                                >
                                    <Icon name={manuallyPaused ? 'play' : 'pause'} className="size-4" strokeWidth={2} />
                                </button>
                            )}
                            <button type="button" onClick={() => go(current + 1, 1)} aria-label={ui.nextSlide}>
                                <Icon name="arrow-right" className="size-4" strokeWidth={2} />
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </section>
    );
}
