import React from "react";

export default function TitleBar({ title, description, action }) {
  return (
    <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
      <div>
        {title && (
          <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">
            {title}
          </h2>
        )}
        {description && (
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {description}
          </p>
        )}
      </div>
      {action && <div>{action}</div>}
    </div>
  );
}
