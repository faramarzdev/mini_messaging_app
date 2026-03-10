export default function FormInput({
  label,
  error,
  className,
  paddingSize,
  ...props
}) {
  let baseClasses =
    "w-full rounded-lg border border-stone-300 dark:border-zinc-600 bg-white dark:bg-slate-700 text-stone-900 " +
    "dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 " +
    "focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all";
  if (className) {
    baseClasses += " " + className;
  }
  if (paddingSize === "xs") {
    baseClasses += " px-2 py-0.5";
  } else if (paddingSize === "sm") {
    baseClasses += " px-1 py-1";
  } else {
    baseClasses += " px-4 py-3";
  }

  return (
    <div>
      {label && (
        <label className="block text-sm font-medium text-slate-800 mb-1 dark:text-gray-200">
          {label}
        </label>
      )}
      <input
        className={`${baseClasses} ${error ? "border-red-500 bg-red-50" : "border-slate-300"}`}
        {...props}
      />
      {error && <p className="text-red-500 text-xs mt-1">{error}</p>}
    </div>
  );
}
