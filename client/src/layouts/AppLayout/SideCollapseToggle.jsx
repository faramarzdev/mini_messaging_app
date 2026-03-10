import React from "react";

export default function SideCollapseToggle({ collapsed, darkMode, setCollapsed }) {
  return (
    <button
      onClick={() => setCollapsed((c) => !c)}
      className={`
              flex items-center w-full rounded-lg px-3 py-2.5 text-sm
              transition-colors
              ${collapsed ? "justify-center" : "gap-3"}
              ${
                darkMode
                  ? "text-slate-400 hover:bg-slate-800/60 hover:text-slate-100"
                  : "text-slate-400 hover:bg-slate-800 hover:text-slate-100"
              }
            `}
    >
      <svg
        className={`w-5 h-5 shrink-0 transition-transform duration-300 ${collapsed ? "rotate-180" : ""}`}
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
        />
      </svg>
      {!collapsed && <span>Collapse</span>}
    </button>
  );
}
