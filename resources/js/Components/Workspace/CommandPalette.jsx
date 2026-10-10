import { router } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import Icon from '../Icon';
import { useWorkspace } from './UI';

/**
 * Ctrl/Cmd + K: jump to any section, start something new, or find a record
 * (listings, pages, requests, applications, people) by name.
 */
export default function CommandPalette({ open, onClose, shortcuts }) {
    const { t } = useWorkspace();
    const [query, setQuery] = useState('');
    const [records, setRecords] = useState([]);
    const [active, setActive] = useState(0);
    const input = useRef(null);

    useEffect(() => {
        if (!open) return undefined;
        const before = document.activeElement;
        setQuery(''); setRecords([]); setActive(0);
        requestAnimationFrame(() => input.current?.focus());
        return () => before?.focus?.();
    }, [open]);

    useEffect(() => {
        const q = query.trim();
        if (!open || q.length < 2) { setRecords([]); return undefined; }
        const controller = new AbortController();
        const timer = setTimeout(() => {
            fetch(`/admin/search?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal })
                .then(response => response.ok ? response.json() : { results: [] })
                .then(data => setRecords(data.results ?? []))
                .catch(() => {});
        }, 180);
        return () => { clearTimeout(timer); controller.abort(); };
    }, [query, open]);

    const results = useMemo(() => {
        const q = query.trim().toLocaleLowerCase();
        const matching = shortcuts.filter(item => !q || `${t(item.label)} ${t(item.group)}`.toLocaleLowerCase().includes(q));
        return [...records, ...matching].slice(0, 14);
    }, [shortcuts, records, query, t]);

    useEffect(() => { setActive(0); }, [results.length]);

    if (!open) return null;
    const go = item => { onClose(); router.visit(item.href); };
    const keydown = event => {
        if (event.key === 'ArrowDown') { event.preventDefault(); setActive(index => Math.min(index + 1, results.length - 1)); }
        else if (event.key === 'ArrowUp') { event.preventDefault(); setActive(index => Math.max(index - 1, 0)); }
        else if (event.key === 'Enter' && results[active]) { event.preventDefault(); go(results[active]); }
        else if (event.key === 'Escape' || event.key === 'Tab') { event.preventDefault(); onClose(); }
    };

    return <div className="ws-palette-overlay" onClick={onClose}>
        <div className="ws-palette" role="dialog" aria-modal="true" aria-label={t('Search or jump to')} onClick={event => event.stopPropagation()}>
            <div className="ws-palette-input">
                <Icon name="search" />
                <input ref={input} role="combobox" aria-expanded="true" aria-controls="ws-palette-results" aria-autocomplete="list" aria-activedescendant={results[active] ? `ws-palette-${active}` : undefined} aria-label={t('Search or jump to')} placeholder={t('Search pages, listings, requests or people…')} value={query} onChange={event => setQuery(event.target.value)} onKeyDown={keydown} />
                <kbd>Esc</kbd>
            </div>
            <ul id="ws-palette-results" role="listbox" aria-label={t('Results')}>
                {results.map((item, index) => <li key={`${item.group}-${item.href}-${item.label}`} id={`ws-palette-${index}`} role="option" aria-selected={index === active}>
                    <button type="button" tabIndex={-1} onMouseEnter={() => setActive(index)} onClick={() => go(item)}>
                        <Icon name={item.icon ?? 'arrow-right'} />
                        <span><strong>{item.record ? item.label : t(item.label)}</strong>{item.hint && <small>{item.hint}</small>}</span>
                        <em>{t(item.group)}</em>
                    </button>
                </li>)}
            </ul>
            {!results.length && <p className="ws-palette-empty">{t('Nothing matches that yet. Try another word.')}</p>}
        </div>
    </div>;
}
