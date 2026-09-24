export default function Avatar({ profile }) {
  return (
    <>
      {profile.featured_picture ? (
        <img
          src={profile.featured_picture}
          alt={profile.name}
          className="w-10 h-10 rounded-full"
        />
      ) : (
        <div className="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
          <span className="text-lg font-bold text-gray-600 dark:text-gray-300">
            {profile.name.charAt(0)}
          </span>
        </div>
      )}
    </>
  );
}
