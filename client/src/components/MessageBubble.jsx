import formatConversationTime from "../utils/formatConversationTime";
import MessageStatus from "./MessageStatus";

export default function MessageBubble({ message, isOwn }) {
  return (
    <div
      className={`flex items-end gap-2 ${isOwn ? "flex-row-reverse" : "flex-row"}`}
    >
      <div
        className={`flex flex-col ${isOwn ? "items-end" : "items-start"} max-w-[65%]`}
      >
        {/* Sender name in group chats */}
        {!isOwn && message.senderName && (
          <span className="text-xs font-semibold text-blue-500 mb-1 ml-3">
            {message.senderName}
          </span>
        )}

        {/* Bubble */}
        <div
          className={`
            px-3.5 py-2 text-sm leading-relaxed break-words
            ${
              isOwn
                ? "bg-blue-600 text-white rounded-[18px_18px_4px_18px]"
                : "bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-[18px_18px_18px_4px]"
            }
          `}
        >
          {message.body}
          <br />
          <span
            className={`inline-flex items-center gap-1 ml-2 text-[11px] float-right mt-1 ${isOwn ? "text-blue-200" : "text-gray-400 dark:text-gray-500"}`}
          >
            {formatConversationTime(message.created_at)}
            {isOwn && <MessageStatus is_read={message.is_read} />}
          </span>
        </div>
      </div>
    </div>
  );
}
