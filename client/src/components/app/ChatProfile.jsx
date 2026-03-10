import React from "react";
import { useTheme } from "../../context/ThemeContext";

export default function ChatProfile({
  user,
  collapsed = false,
  onClick = () => {},
}) {
  const { darkMode } = useTheme();
  const initials = user?.name
    ? user.name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2)
    : "D";

  return (
    <>
      <div className="flex items-center gap-3" onClick={() => onClick()}>
        <div className="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
          {initials}
        </div>
        {!collapsed && (
          <div className="min-w-0">
            <p
              className={`text-sm font-semibold truncate ${darkMode ? "text-slate-200" : "text-slate-700"}`}
            >
              {user?.name ?? "Deleted Account"}
            </p>
            <p
              className={`text-xs truncate ${darkMode ? "text-slate-500" : "text-slate-400"}`}
            >
              {user?.email ?? ""}
            </p>
          </div>
        )}
      </div>
    </>
  );
}
