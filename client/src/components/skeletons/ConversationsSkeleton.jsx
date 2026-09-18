import ConversationSkeleton from "./ConversationSkeleton";

export default function ConversationsSkeleton() {
  return Array.from({ length: 10 }).map((_, i) => (
    <>
      <ConversationSkeleton key={i} />
    </>
  ));
}
