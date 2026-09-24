import { useState, useRef, useEffect } from "react";
import { useAutoResize } from "../hooks/useAutoResize";
import { sendMessage } from "../api/messages";

/**
 * MessageInput
 *
 * Props:
 *   onSend  function(text: string)
 */
export default function MessageInput({ otherProfile }) {
  const [text, setText] = useState("");
  const [attachOpen, setAttachOpen] = useState(false);
  const textareaRef = useAutoResize(text, 5);
  const attachRef = useRef(null);

  // Close attach menu on outside click
  useEffect(() => {
    function handle(e) {
      if (attachRef.current && !attachRef.current.contains(e.target)) {
        setAttachOpen(false);
      }
    }
    document.addEventListener("mousedown", handle);
    return () => document.removeEventListener("mousedown", handle);
  }, []);

  async function handleSend() {
    // todo: reply id
    const trimmed = text.trim();
    if (!trimmed) return;
    try {
      await sendMessage(trimmed, otherProfile.handle);
      setText("");
    } catch (err) {
      console.log("Failed to send message:", err);
    }
  }

  function handleKeyDown(e) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  }

  const hasText = text.trim().length > 0;

  return (
    <div className="flex-shrink-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 p-3">
      <div className="flex items-end gap-2">
        <div className="flex-1 bg-gray-100 dark:bg-gray-800 rounded-2xl px-4 py-2 flex items-end">
          <textarea
            ref={textareaRef}
            value={text}
            onChange={(e) => setText(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder="Message"
            rows={1}
            className="
              w-full bg-transparent resize-none outline-none
              text-sm text-gray-900 dark:text-gray-100
              placeholder-gray-400 dark:placeholder-gray-500
              leading-relaxed max-h-32 overflow-y-auto scrollbar-thin
            "
          />
        </div>

        <button
          onClick={hasText ? handleSend : undefined}
          className={`
            flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full transition-all
            ${
              hasText
                ? "bg-blue-600 hover:bg-blue-700 text-white shadow-sm"
                : "bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500"
            }
          `}
          aria-label={hasText ? "Send" : "Voice message"}
        >
          <SendIcon className="w-5 h-5" />
        </button>
      </div>
    </div>
  );
}

// ─── Icons ────────────────────────────────────────────────────────────────────
const s = {
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 2,
  strokeLinecap: "round",
  strokeLinejoin: "round",
};

function SendIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...s}>
      <line x1="22" y1="2" x2="11" y2="13" />
      <polygon points="22 2 15 22 11 13 2 9 22 2" />
    </svg>
  );
}
