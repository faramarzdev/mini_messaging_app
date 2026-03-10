import LogoSvg from "../../components/LogoSvg";

export default function SideBrand({ collapsed, darkMode }) {
  return (
    <div
      className={`
              flex items-center h-16 shrink-0 overflow-hidden
              ${collapsed ? "justify-center px-0" : "gap-3 px-5"}
              ${darkMode ? "border-b border-slate-800" : "border-b border-slate-700/60"}
            `}
    >
      <LogoSvg />

      {!collapsed && (
        <span className="text-sm font-semibold tracking-wide text-white whitespace-nowrap">
          Admin Panel
        </span>
      )}
    </div>
  );
}
