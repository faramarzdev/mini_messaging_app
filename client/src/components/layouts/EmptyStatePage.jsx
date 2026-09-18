import { Link } from "react-router-dom";
import { ROUTES } from "../../routes/paths.js";
export default function EmptyStatePage() {
  return (
    <div className="flex-1 flex flex-col items-center justify-center h-full bg-gray-50 dark:bg-gray-950 select-none">
      <div className="w-20 h-20 rounded-full bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center mb-5">
        <ChatBubbleIcon className="w-10 h-10 text-blue-500 dark:text-blue-400" />
      </div>
      <h2 className="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">
        Select a conversation
      </h2>
      <p className="text-sm text-gray-400 dark:text-gray-500 text-center max-w-xs px-6">
        Choose from your existing conversations or start a new one.
      </p>

      <Link
        to={ROUTES.appContacts}
        className="mt-6 flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-full shadow transition-colors"
      >
        <PencilIcon className="w-4 h-4" />
        New Message
      </Link>
    </div>
  );
}

const s = {
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 2,
  strokeLinecap: "round",
  strokeLinejoin: "round",
};

function ChatBubbleIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...s}>
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </svg>
  );
}

function PencilIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...s}>
      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
    </svg>
  );
}
