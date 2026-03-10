import { useState, useEffect } from "react";

export default function useDebounce(value, delay = 500) {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    // Set timer to update debounced value after delay
    const timer = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    // Cleanup: cancel timer if value changes before delay!
    return () => clearTimeout(timer);
  }, [value, delay]);

  return debouncedValue;
}
