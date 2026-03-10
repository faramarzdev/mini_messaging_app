export default function AlertSuccess({ message }) {
  return (
    <div className="text-green-500 font-bold text-lg border border-green-600 rounded-lg p-4 bg-green-500/10">
      {message}
    </div>
  );
}
