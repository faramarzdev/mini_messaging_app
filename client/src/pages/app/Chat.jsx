import { useNavigate, useParams } from "react-router-dom";
import api from "../../config/api";

export default function Chat() {
  const { id } = useParams();
  const navigate = useNavigate();

  return <div>Chat {id}</div>;
}
