import SkeletonBase from "./SkeletonBase";

export default function ConversationSkeleton() {
  return (
    <>
      <div className="flex items-center gap-3 px-3 py-2">
        {/* Avatar */}
        <SkeletonBase className="w-10 h-10 rounded-full flex-shrink-0" />

        {/* Body */}
        <div className="flex-1 min-w-0 text-sm">
          {/* Top row: name + time */}
          <div className="flex justify-between w-full">
            <SkeletonBase className="w-28 h-4" />
            <SkeletonBase className="w-10 h-3 ml-2" />
          </div>

          {/* Bottom row: preview + unread badge */}
          <div className="flex items-center justify-between gap-2 mt-1.5">
            <SkeletonBase className="flex-1 h-3" />
            <SkeletonBase className="w-6 h-4 rounded-full flex-shrink-0" />
          </div>
        </div>
      </div>
    </>
  );
}
