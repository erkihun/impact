import './bootstrap';

import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const overlayController = () => ({
    mobileOpen: false,
    activeMenu: null,
    trigger: null,

    // Per-menu getters: the CSP build binds to properties, not expressions.
    get aboutExpanded() {
        return this.activeMenu === 'about';
    },

    get servicesExpanded() {
        return this.activeMenu === 'services';
    },

    get industriesExpanded() {
        return this.activeMenu === 'industries';
    },

    get insightsExpanded() {
        return this.activeMenu === 'insights';
    },

    toggleMenu(event) {
        const trigger = event.currentTarget;
        const menu = trigger?.dataset.menu;

        if (! menu) {
            return;
        }

        this.trigger = trigger;
        this.activeMenu = this.activeMenu === menu ? null : menu;
    },

    closeMenu(restoreFocus = false) {
        this.activeMenu = null;

        if (restoreFocus === true && this.trigger instanceof HTMLElement) {
            this.trigger.focus();
        }
    },

    handleEscape() {
        if (this.mobileOpen) {
            this.closeMobile();

            return;
        }

        if (this.activeMenu !== null) {
            this.closeMenu(true);
        }
    },

    closeMobileOnEscape() {
        if (this.mobileOpen) {
            this.closeMobile();
        }
    },

    openMobile(event) {
        this.trigger = event.currentTarget;
        this.mobileOpen = true;
        document.body.style.overflow = 'hidden';

        this.$nextTick(() => this.$refs.mobileClose?.focus());
    },

    closeMobile() {
        this.mobileOpen = false;
        document.body.style.overflow = '';

        // Focus returns to whatever opened the sheet, per the overlay pattern.
        if (this.trigger instanceof HTMLElement) {
            this.$nextTick(() => this.trigger.focus());
        }
    },

    trapFocus(event) {
        if (event.key !== 'Tab' || ! this.mobileOpen) {
            return;
        }

        const root = this.$refs.mobileSheet;
        const controls = root ? [...root.querySelectorAll(focusableSelector)] : [];

        if (controls.length === 0) {
            return;
        }

        const first = controls[0];
        const last = controls[controls.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
});

Alpine.data('publicNavigation', overlayController);
Alpine.data('adminNavigation', () => ({
    ...overlayController(),
    expanded: true,

    init() {
        try {
            const saved = window.localStorage.getItem('impact.admin.sidebar.expanded');
            const defaultExpanded = document.body.dataset.adminSidebarDefault !== 'collapsed';
            this.expanded = saved === null ? defaultExpanded : saved !== 'false';
        } catch {
            this.expanded = document.body.dataset.adminSidebarDefault !== 'collapsed';
        }
    },

    get shellClass() {
        return this.expanded ? 'admin-shell-expanded' : 'admin-shell-collapsed';
    },

    get collapseLabel() {
        return this.expanded
            ? this.$el.querySelector('[data-label-collapse]')?.dataset.labelCollapse ?? 'Collapse navigation'
            : this.$el.querySelector('[data-label-expand]')?.dataset.labelExpand ?? 'Expand navigation';
    },

    get collapseIconClass() {
        return this.expanded ? '' : 'admin-collapse-icon-collapsed';
    },

    toggleRail() {
        this.expanded = ! this.expanded;

        try {
            window.localStorage.setItem('impact.admin.sidebar.expanded', this.expanded ? 'true' : 'false');
        } catch {
            // Preference persistence is non-critical.
        }
    },
}));
Alpine.data('dropdown', () => ({
    open: false,
    trigger: null,

    toggle(event) {
        this.trigger = event.currentTarget;
        this.open = ! this.open;

        if (this.open) {
            this.$nextTick(() => this.$refs.menu?.querySelector(focusableSelector)?.focus());
        }
    },

    close(restoreFocus = false) {
        this.open = false;

        if (restoreFocus && this.trigger instanceof HTMLElement) {
            this.$nextTick(() => this.trigger.focus());
        }
    },
}));

Alpine.data('settingsForm', () => ({
    dirty: false,
    changed: 0,

    markChanged() {
        this.changed += 1;
        this.dirty = true;
    },
}));

Alpine.data('homepageHeroSlider', () => ({
    current: 1,
    count: 1,
    interval: 7000,
    autoplay: true,
    pauseOnHover: true,
    manuallyPaused: false,
    pointerPaused: false,
    focusPaused: false,
    timer: null,
    resizeFrame: null,
    resizeHandler: null,

    init() {
        this.count = Math.max(1, Number(this.$el.dataset.slideCount ?? 1));
        this.interval = Math.max(4000, Number(this.$el.dataset.interval ?? 7000));
        this.autoplay = this.$el.dataset.autoplay === 'true';
        this.pauseOnHover = this.$el.dataset.pauseOnHover === 'true';

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.autoplay = false;
        }

        this.resizeHandler = () => this.queueStageMeasurement();
        window.addEventListener('resize', this.resizeHandler);

        this.$nextTick(() => {
            this.measureStage();

            if (document.fonts?.ready) {
                document.fonts.ready.then(() => this.queueStageMeasurement());
            }
        });

        this.schedule();
    },

    destroy() {
        this.stop();

        if (this.resizeHandler !== null) {
            window.removeEventListener('resize', this.resizeHandler);
        }

        if (this.resizeFrame !== null) {
            window.cancelAnimationFrame(this.resizeFrame);
        }
    },

    get paused() {
        return this.manuallyPaused || this.pointerPaused || this.focusPaused;
    },

    get pauseButtonVisible() {
        return this.autoplay && ! this.manuallyPaused;
    },

    get playButtonVisible() {
        return ! this.autoplay || this.manuallyPaused;
    },

    get slide1Visible() { return this.current === 1; },
    get slide2Visible() { return this.current === 2; },
    get slide3Visible() { return this.current === 3; },

    get slide1Current() { return this.current === 1 ? 'true' : null; },
    get slide2Current() { return this.current === 2 ? 'true' : null; },
    get slide3Current() { return this.current === 3 ? 'true' : null; },

    get slide1DotClass() { return this.dotClass(1); },
    get slide2DotClass() { return this.dotClass(2); },
    get slide3DotClass() { return this.dotClass(3); },

    dotClass(index) {
        return this.current === index ? 'home-hero-slider-dot-active' : '';
    },

    queueStageMeasurement() {
        if (this.resizeFrame !== null) {
            window.cancelAnimationFrame(this.resizeFrame);
        }

        this.resizeFrame = window.requestAnimationFrame(() => {
            this.resizeFrame = null;
            this.measureStage();
        });
    },

    measureStage() {
        const stage = this.$refs.stage;
        const slides = [...this.$el.querySelectorAll('[data-home-hero-slide]')];

        if (! stage || slides.length === 0) {
            return;
        }

        const originalStageHeight = stage.style.height;
        const originalSlideStyles = slides.map((slide) => slide.getAttribute('style'));

        stage.style.height = 'auto';

        slides.forEach((slide) => {
            slide.style.setProperty('display', 'block');
            slide.style.setProperty('visibility', 'hidden');
        });

        const maximumHeight = Math.ceil(stage.getBoundingClientRect().height);

        slides.forEach((slide, index) => {
            const originalStyle = originalSlideStyles[index];

            if (originalStyle === null) {
                slide.removeAttribute('style');

                return;
            }

            slide.setAttribute('style', originalStyle);
        });

        stage.style.height = maximumHeight > 0
            ? `${maximumHeight}px`
            : originalStageHeight;
    },

    goToSlide(event) {
        const requested = Number(event.currentTarget?.dataset.slideTarget);

        if (! Number.isInteger(requested) || requested < 1 || requested > this.count) {
            return;
        }

        this.current = requested;
        this.restart();
    },

    previousSlide() {
        this.current = this.current <= 1 ? this.count : this.current - 1;
        this.restart();
    },

    nextSlide() {
        this.current = this.current >= this.count ? 1 : this.current + 1;
        this.restart();
    },

    pauseFromPointer() {
        if (! this.pauseOnHover) {
            return;
        }

        this.pointerPaused = true;
        this.stop();
    },

    resumeFromPointer() {
        this.pointerPaused = false;
        this.schedule();
    },

    pauseFromFocus() {
        this.focusPaused = true;
        this.stop();
    },

    resumeFromFocus(event) {
        if (event.relatedTarget instanceof Node && this.$el.contains(event.relatedTarget)) {
            return;
        }

        this.focusPaused = false;
        this.schedule();
    },

    pauseManually() {
        this.manuallyPaused = true;
        this.stop();
    },

    playManually() {
        this.autoplay = true;
        this.manuallyPaused = false;
        this.schedule();
    },

    restart() {
        this.stop();
        this.schedule();
    },

    schedule() {
        if (! this.autoplay || this.paused || this.count <= 1 || this.timer !== null) {
            return;
        }

        this.timer = window.setInterval(() => this.nextSlide(), this.interval);
    },

    stop() {
        if (this.timer === null) {
            return;
        }

        window.clearInterval(this.timer);
        this.timer = null;
    },
}));

Alpine.data('modal', () => ({
    show: false,
    name: null,
    trigger: null,

    init() {
        this.name = this.$el.dataset.modalName ?? null;

        if (this.$el.dataset.modalShow === 'true') {
            this.show = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.dialog?.querySelector(focusableSelector)?.focus());
        }
    },

    openFromEvent(event) {
        if (event.detail !== this.name) {
            return;
        }

        this.trigger = document.activeElement;
        this.show = true;
        document.body.style.overflow = 'hidden';
        this.$nextTick(() => this.$refs.dialog?.querySelector(focusableSelector)?.focus());
    },

    closeFromEvent(event) {
        // A bare `close` dispatch closes whichever modal is open.
        if (event.detail === undefined || event.detail === this.name) {
            this.closeModal();
        }
    },

    closeOnEscape() {
        if (this.show) {
            this.closeModal();
        }
    },

    closeModal() {
        if (! this.show) {
            return;
        }

        this.show = false;
        document.body.style.overflow = '';

        if (this.trigger instanceof HTMLElement) {
            this.$nextTick(() => this.trigger.focus());
        }
    },

    trapModal(event) {
        if (event.key !== 'Tab' || ! this.show) {
            return;
        }

        const controls = this.$refs.dialog
            ? [...this.$refs.dialog.querySelectorAll(focusableSelector)]
            : [];

        if (controls.length === 0) {
            return;
        }

        const first = controls[0];
        const last = controls[controls.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

Alpine.data('multiStepForm', (totalSteps = 4, initialStep = 1) => ({
    step: initialStep,
    totalSteps,

    // Derived state: the CSP build binds to properties, never to expressions.
    get onStep1() { return this.step === 1; },
    get onStep2() { return this.step === 2; },
    get onStep3() { return this.step === 3; },
    get onStep4() { return this.step === 4; },
    get canGoBack() { return this.step > 1; },
    get canGoForward() { return this.step < this.totalSteps; },
    get onFinalStep() { return this.step === this.totalSteps; },

    stepClassFor(index) {
        if (this.step === index) {
            return 'bg-action-500 text-white';
        }

        return this.step > index ? 'bg-action-50 text-action-800' : 'bg-quiet text-muted';
    },

    get stepClass1() { return this.stepClassFor(1); },
    get stepClass2() { return this.stepClassFor(2); },
    get stepClass3() { return this.stepClassFor(3); },
    get stepClass4() { return this.stepClassFor(4); },

    get stepCurrent1() { return this.step === 1 ? 'step' : null; },
    get stepCurrent2() { return this.step === 2 ? 'step' : null; },
    get stepCurrent3() { return this.step === 3 ? 'step' : null; },
    get stepCurrent4() { return this.step === 4 ? 'step' : null; },

    nextStep() {
        const section = this.$refs.form?.querySelector(`[data-step="${this.step}"]`);
        const fields = section ? [...section.querySelectorAll('input, select, textarea')] : [];
        const invalid = fields.find((field) => ! field.checkValidity());

        if (invalid) {
            invalid.reportValidity();
            invalid.focus();

            return;
        }

        if (this.step < this.totalSteps) {
            this.step += 1;
            this.afterStepChange();
        }
    },

    previousStep() {
        if (this.step > 1) {
            this.step -= 1;
            this.afterStepChange();
        }
    },

    goToStep(event) {
        const requested = Number(event.currentTarget?.dataset.stepTarget);

        if (! Number.isFinite(requested)) {
            return;
        }

        const requestedStep = Math.min(Math.max(requested, 1), this.totalSteps);

        // Only allow jumping back to a step already completed.
        if (requestedStep > this.step) {
            return;
        }

        this.step = requestedStep;
        this.afterStepChange();
    },

    afterStepChange() {
        this.populateSummary();
        this.focusStep();
    },

    // The review step mirrors entered values; read them straight from the form
    // so no per-field Alpine binding is required under the CSP build.
    populateSummary() {
        const form = this.$refs.form;

        if (! form) {
            return;
        }

        form.querySelectorAll('[data-summary-field]').forEach((cell) => {
            const field = form.elements.namedItem(cell.dataset.summaryField);
            const value = field instanceof RadioNodeList ? field.value : field?.value;
            cell.textContent = value?.trim() ? value : '—';
        });
    },

    focusStep() {
        this.$nextTick(() => {
            this.$refs.form
                ?.querySelector(`[data-step="${this.step}"] [data-step-heading]`)
                ?.focus();
        });
    },

}));

const CONSENT_STORAGE_KEY = 'impact.consent';
const OPTIONAL_CONSENT_CATEGORIES = ['preferences', 'analytics', 'marketing'];

const readStoredConsent = (policyVersion) => {
    try {
        const raw = window.localStorage.getItem(CONSENT_STORAGE_KEY);

        if (! raw) {
            return null;
        }

        const stored = JSON.parse(raw);

        // A new policy version invalidates prior consent and re-asks the user.
        return stored?.policyVersion === policyVersion ? stored : null;
    } catch {
        return null;
    }
};

Alpine.data('consentManager', () => ({
    open: false,
    detailsOpen: false,
    saving: false,
    failed: false,
    policyVersion: '',
    endpoint: '',
    labels: { allowed: 'Allowed', notAllowed: 'Not allowed' },
    decisions: { preferences: false, analytics: false, marketing: false },

    init() {
        const root = this.$el;
        this.policyVersion = root.dataset.policyVersion ?? '';
        this.endpoint = root.dataset.endpoint ?? '';
        this.labels = {
            allowed: root.dataset.labelAllowed ?? this.labels.allowed,
            notAllowed: root.dataset.labelNotAllowed ?? this.labels.notAllowed,
        };

        const stored = readStoredConsent(this.policyVersion);

        if (stored) {
            this.decisions = { ...this.decisions, ...stored.decisions };

            return;
        }

        this.open = true;
        this.$nextTick(() => this.$refs.banner?.focus());
    },

    // Derived state: the CSP build resolves directives to properties, not expressions.
    get bannerVisible() {
        return this.open && ! this.detailsOpen;
    },

    get idle() {
        return ! this.saving;
    },

    get preferencesAllowed() {
        return this.decisions.preferences;
    },

    get analyticsAllowed() {
        return this.decisions.analytics;
    },

    get marketingAllowed() {
        return this.decisions.marketing;
    },

    get preferencesLabel() {
        return this.decisions.preferences ? this.labels.allowed : this.labels.notAllowed;
    },

    get analyticsLabel() {
        return this.decisions.analytics ? this.labels.allowed : this.labels.notAllowed;
    },

    get marketingLabel() {
        return this.decisions.marketing ? this.labels.allowed : this.labels.notAllowed;
    },

    togglePreferences() {
        this.decisions.preferences = ! this.decisions.preferences;
    },

    toggleAnalytics() {
        this.decisions.analytics = ! this.decisions.analytics;
    },

    toggleMarketing() {
        this.decisions.marketing = ! this.decisions.marketing;
    },

    acceptAll() {
        this.persist(Object.fromEntries(OPTIONAL_CONSENT_CATEGORIES.map((key) => [key, true])));
    },

    rejectAll() {
        this.persist(Object.fromEntries(OPTIONAL_CONSENT_CATEGORIES.map((key) => [key, false])));
    },

    saveSelection() {
        this.persist({ ...this.decisions });
    },

    showDetails() {
        this.detailsOpen = true;
        this.$nextTick(() => this.$refs.detailsHeading?.focus());
    },

    hideDetails() {
        this.detailsOpen = false;
    },

    reopen() {
        this.failed = false;
        this.open = true;
        this.detailsOpen = true;
        this.$nextTick(() => this.$refs.detailsHeading?.focus());
    },

    async persist(decisions) {
        this.saving = true;
        this.failed = false;

        // Necessary storage is always recorded as granted; it is not optional.
        const payload = { necessary: true, ...decisions };

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ decisions: payload, policy_version: this.policyVersion }),
            });

            if (! response.ok) {
                throw new Error('Consent was not recorded.');
            }

            window.localStorage.setItem(
                CONSENT_STORAGE_KEY,
                JSON.stringify({
                    policyVersion: this.policyVersion,
                    decisions: payload,
                    recordedAt: new Date().toISOString(),
                }),
            );

            this.decisions = { ...this.decisions, ...decisions };
            this.open = false;
            this.detailsOpen = false;
            window.dispatchEvent(new CustomEvent('consent-updated', { detail: payload }));
        } catch {
            this.failed = true;
        } finally {
            this.saving = false;
        }
    },

    trapConsent(event) {
        if (event.key !== 'Tab') {
            return;
        }

        const root = this.$refs.details;
        const controls = root ? [...root.querySelectorAll(focusableSelector)] : [];

        if (controls.length === 0) {
            return;
        }

        const first = controls[0];
        const last = controls[controls.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const errorSummary = document.querySelector('[data-error-summary]');

    if (errorSummary instanceof HTMLElement) {
        errorSummary.focus();
    }

    // Modal triggers, declared through data attributes so no inline expression is needed.
    document.querySelectorAll('[data-open-modal]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            window.dispatchEvent(new CustomEvent('open-modal', {
                detail: trigger.dataset.openModal,
            }));
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            const name = trigger.dataset.closeModal;
            window.dispatchEvent(new CustomEvent('close-modal', {
                detail: name === '' ? undefined : name,
            }));
        });
    });

    document.querySelectorAll('[data-open-consent-preferences]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('open-consent-preferences'));
        });
    });

    document.querySelectorAll('form[data-prevent-duplicate]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.setAttribute('aria-busy', 'true');

            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
            });
        });
    });
});
