import "dotenv/config";
import cors from "cors";
import express from "express";
import helmet from "helmet";
import morgan from "morgan";
import { createRouter } from "./routes.js";

const app = express();
const port = Number(process.env.PORT ?? 8787);

app.use(helmet());
app.use(cors());
app.use(express.json({ limit: "1mb" }));
app.use(morgan("combined"));
app.use(createRouter());

app.use((_req, res) => {
  res.status(404).json({
    error: {
      code: "not_found",
      message: "Route not found.",
    },
  });
});

app.listen(port, () => {
  console.log(`Analytics Chat bridge listening on http://localhost:${port}`);
});
