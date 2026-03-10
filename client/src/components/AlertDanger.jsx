export default function AlertDanger({ message }) {
  return (
    <div className="text-xl text-red-600 font-semibold border rounded-3xl border-red-600 text-center my-5 p-5">
      {message}
    </div>
  );
}
