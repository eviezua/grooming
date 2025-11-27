const debounceMap = new WeakMap();

export const useDebounce = (fn, delay = 400) => {
    if (typeof fn !== 'function') return fn;

    return (...args) => {
        let timer = debounceMap.get(fn);
        if (timer) clearTimeout(timer);

        timer = setTimeout(() => {
            fn(...args);
            debounceMap.delete(fn);
        }, delay);

        debounceMap.set(fn, timer);
    };
};