import { NavLink } from "react-router-dom";
import formatConversationTime from "../utils/formatConversationTime";

export default function ConversationItem({ conversation, loggedInUserId }) {
  return (
    <NavLink
      to={`/app/chat/${conversation.id}`}
      className="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer"
    >
      {conversation.profile.picture_url ? (
        <img
          src={conversation.profile.picture_url}
          alt={conversation.profile.name}
          className="w-10 h-10 rounded-full"
        />
      ) : (
        <div className="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
          <span className="text-lg font-bold text-gray-600 dark:text-gray-300">
            {conversation.profile.name.charAt(0)}
          </span>
        </div>
      )}
      <div className="flex-1 min-w-0 text-sm text-gray-900 dark:text-gray-300">
        <div className="flex justify-between w-full font-bold">
          <span>{conversation.profile.name}</span>
          {conversation.last_message && (
            <span className="ml-2 text-xs text-gray-400 dark:text-gray-500">
              {formatConversationTime(conversation.last_message.created_at)}
            </span>
          )}
        </div>
        <div className="flex items-center justify-between gap-2 text-xs text-gray-400 dark:text-gray-500 overflow-hidden text-ellipsis whitespace-nowrap">
          <div className="flex-1 min-w-0 truncate">
            {conversation.last_message ? (
              <>
                {/* todo: Add proper sender name display (specifically when it is in group) */}
                <span className="font-bold text-gray-500 dark:text-gray-300">
                  {conversation.last_message.sender === loggedInUserId &&
                    "You: "}
                </span>
                {conversation.last_message.body}
              </>
            ) : (
              <>No messages yet</>
            )}
          </div>
          {conversation.unread_count > 0 && (
            <span className="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
              {conversation.unread_count}
            </span>
          )}
        </div>
      </div>
    </NavLink>
  );
}
