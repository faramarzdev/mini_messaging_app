export default function Button({ children, className, ...props }) {
  let baseClasses =
    "flex items-center justify-center py-3 px-4 rounded-lg bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 " +
    "dark:from-amber-500 dark:to-orange-500 dark:hover:from-amber-600 dark:hover:to-orange-600 text-white font-semibold " +
    "shadow-md shadow-amber-500/30 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:shadow-lg hover:shadow-amber-500/40";
  if (className) {
    baseClasses += ` ${className}`;
  }

  return (
    <button className={baseClasses} {...props}>
      {children}
    </button>
  );
}
