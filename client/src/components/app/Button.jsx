import { Link } from "react-router-dom";

export default function Button({
  to,
  children,
  variant = "primary",
  className = "",
  ...props
}) {
  const baseClasses =
    "px-4 py-2 rounded-lg transition-colors disabled:opacity-50 flex items-center gap-2";

  const variants = {
    primary: "bg-blue-600 hover:bg-blue-700 text-white",
    danger: "bg-red-600 hover:bg-red-700 text-white",
    warning: "bg-orange-600 hover:bg-orange-700 text-white",
    success: "bg-green-600 hover:bg-green-700 text-white",
    secondary:
      "bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300",
  };

  const variantClasses = variants[variant];
  const finalClasses = `${baseClasses} ${variantClasses} ${className}`;

  const Component = to ? Link : "button";
  return (
    <Component to={to} className={finalClasses} {...props}>
      {children}
    </Component>
  );
}
