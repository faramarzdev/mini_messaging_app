import { useNavigate, useParams } from "react-router-dom";
import MessageBubble from "../../components/MessageBubble";
import { useAuth } from "../../context/AuthContext";
import useMessages from "../../hooks/useMessages";
import LoadingOverlay from "../../components/ui/LoadingOverlay";
import { ROUTES } from "../../routes/paths";
import ChatHeader from "../../components/ChatHeader";
import MessageInput from "../../components/MessageInput";
import useProfile from "../../hooks/useProfile";

export default function ChatPage() {
  const { handle } = useParams();
  const navigate = useNavigate();

  const { user, loading } = useAuth();
  const { messages, isLoading, isError, error } = useMessages(handle);
  const {
    profile: otherProfile,
    isLoading: isProfileLoading,
    isError: isProfileError,
    error: profileError,
  } = useProfile(handle);

  if (loading) return <LoadingOverlay />;
  if (isProfileLoading) return <div>Loading profile...</div>;
  if (isProfileError)
    return <div>Error loading profile: {profileError.message}</div>;
  if (isError) return <div>Error loading messages: {error.message}</div>;
  if (!otherProfile) return <div>Todo: Contact not found!</div>;

  return (
    <>
      <div className="flex flex-col w-full h-full min-h-0">
        <ChatHeader
          profile={otherProfile}
          onBack={() => navigate(ROUTES.app)}
        />
        <div className="p-5 flex-1 overflow-y-auto space-y-4">
          {isLoading ? (
            <div>Loading messages...</div>
          ) : messages.length === 0 ? (
            <div className="bg-gray-300 dark:bg-gray-700 rounded-full py-5 px-12 text-gray-800 dark:text-gray-200 text-lg font-semibold text-center w-60 mx-auto">
              No messages yet!
            </div>
          ) : messages.length > 0 ? (
            <div className="w-full space-y-4">
              {messages.map((message) => (
                <MessageBubble
                  key={message.id}
                  message={message}
                  isOwn={message.sender.handle === user.profile.handle}
                />
              ))}
            </div>
          ) : (
            <div>An error occurred while loading messages.</div>
          )}
        </div>
        <MessageInput otherProfile={otherProfile} />
      </div>
    </>
  );
}
