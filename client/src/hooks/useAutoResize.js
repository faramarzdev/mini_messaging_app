import { useEffect, useRef } from 'react';

/**
 * Attaches to a textarea ref and auto-resizes it up to maxRows.
 */
export function useAutoResize(value, maxRows = 5) {
  const ref = useRef(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    el.style.height = 'auto';
    const lineHeight = parseInt(getComputedStyle(el).lineHeight, 10) || 20;
    const maxHeight = lineHeight * maxRows + 16; // + vertical padding
    el.style.height = Math.min(el.scrollHeight, maxHeight) + 'px';
  }, [value, maxRows]);

  return ref;
}
