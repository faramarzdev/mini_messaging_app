export default function AuthInput({
  label,
  type,
  id,
  value,
  onChange,
  className,
  iconSvg,
  placeholder,
  hint,
}) {
  let inputClassName =
    "w-full pl-10 pr-4 py-2.5 rounded-xl border border-stone-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 " +
    "placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition";
  if (className) {
    inputClassName = inputClassName + " " + className;
  }

  return (
    <>
      <div className="input-group" id={id}>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
          {label}
        </label>
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
            {iconSvg}
          </div>
          <input
            type={type}
            placeholder={placeholder}
            value={value}
            onChange={onChange}
            className={inputClassName}
          />
        </div>
        {hint && (
          <p className="text-xs text-gray-400 dark:text-gray-500 mt-1.5">
            {hint}
          </p>
        )}
      </div>
    </>
  );
}
