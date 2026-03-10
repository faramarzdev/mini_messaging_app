export default function Card({ children, className }) {
  let baseClasses =
    "group relative bg-white dark:bg-zinc-800 rounded-xl border border-stone-200 dark:border-zinc-700 overflow-hidden transition-all duration-300 my-5";
  if (className) {
    baseClasses += ` ${className}`;
  }
  return <div className={baseClasses}>{children}</div>;
}
