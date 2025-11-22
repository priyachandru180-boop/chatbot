const express = require("express");
const cors = require("cors");
const fs = require("fs").promises;
const path = require("path");
const mysql = require("mysql2/promise");
const axios = require("axios");

const app = express();
const PORT = 3002;

app.use(cors());
app.use(express.json());

// === AnythingLLM CONFIG ===
const WORKSPACE_ID = "priyanka";
const API_KEY = "VN9NXWB-JT6MZJF-MB81JG6-D2X6YKB";
const BASE_URL = "http://localhost:3001/api";

const dbConfig = {
    host: "localhost",
    user: "root",
    password:"",
    database: "institution2",
};

async function connectDB() {
    return mysql.createConnection(dbConfig);
}

// ===============================
// 1) EXPORT DATABASE
// ===============================
app.get("/api/export-database", async (req, res) => {
    try {
        const connection = await connectDB();
        const [tables] = await connection.execute("SHOW TABLES");

        const exportDir = "./exports";
        await fs.mkdir(exportDir, { recursive: true });

        let exportedFiles = [];

        for (let row of tables) {
            const tableName = Object.values(row)[0];
            const [rows] = await connection.execute(`SELECT * FROM ${tableName}`);

            const filename = `${tableName}_${Date.now()}.txt`;
            const filepath = path.join(exportDir, filename);

            const content =
                `TABLE: ${tableName}\n\n` +
                JSON.stringify(rows, null, 4) +
                `\n\nExported: ${new Date().toISOString()}`;

            await fs.writeFile(filepath, content);

            exportedFiles.push({
                table: tableName,
                file: filename,
            });
        }

        await connection.end();

        res.json({
            success: true,
            message: "Database export completed",
            totalFiles: exportedFiles.length,
            files: exportedFiles,
        });

    } catch (err) {
        console.error(err);
        res.status(500).json({
            success: false,
            message: "Error exporting database",
            error: err.message,
        });
    }
});

// ===============================
// 2) BULK UPLOAD TO WORKSPACE - SIMPLIFIED
// ===============================
app.post("/api/bulk-upload-exports", async (req, res) => {
    try {
        const exportDir = "./exports";
        
        try {
            await fs.access(exportDir);
        } catch (err) {
            return res.status(400).json({
                success: false,
                message: "Exports directory doesn't exist. Please export database first.",
            });
        }

        const files = await fs.readdir(exportDir);
        const txtFiles = files.filter(file => file.endsWith('.txt'));

        if (txtFiles.length === 0) {
            return res.status(400).json({
                success: false,
                message: "No exported .txt files found in exports folder.",
            });
        }

        let results = [];
        let successCount = 0;
        let errorCount = 0;

        console.log(`Starting bulk upload of ${txtFiles.length} files...`);

        for (const file of txtFiles) {
            try {
                const filePath = path.join(exportDir, file);
                const content = await fs.readFile(filePath, "utf8");
                
                console.log(`Uploading ${file}...`);

                // Try the document upload endpoint
                const response = await axios.post(
                    `${BASE_URL}/workspace/${WORKSPACE_ID}/document`,
                    {
                        fileName: file,
                        fileType: "text/plain",
                        content: content
                    },
                    {
                        headers: {
                            'Authorization': `Bearer ${API_KEY}`,
                            'Content-Type': 'application/json'
                        }
                    }
                );

                successCount++;
                results.push({
                    file,
                    success: true,
                    message: "Upload successful"
                });
                console.log(`✅ Successfully uploaded: ${file}`);

                // Wait between uploads
                await new Promise(resolve => setTimeout(resolve, 500));

            } catch (err) {
                errorCount++;
                const errorMsg = err.response?.data?.message || err.message;
                results.push({
                    file,
                    success: false,
                    error: errorMsg
                });
                console.error(`❌ Error uploading ${file}:`, errorMsg);
            }
        }

        console.log(`Bulk upload completed: ${successCount} successful, ${errorCount} failed`);

        res.json({
            success: successCount > 0,
            message: `Bulk upload completed. ${successCount} files uploaded successfully, ${errorCount} files failed.`,
            results: results
        });

    } catch (err) {
        console.error("Bulk upload error:", err);
        res.status(500).json({
            success: false,
            message: "Bulk upload failed",
            error: err.message
        });
    }
});

// ===============================
// 3) WORKSPACE INFO
// ===============================
app.get("/api/workspace-info", async (req, res) => {
    try {
        const response = await axios.get(
            `${BASE_URL}/workspace/${WORKSPACE_ID}`,
            {
                headers: {
                    Authorization: `Bearer ${API_KEY}`
                }
            }
        );

        res.json({
            success: true,
            workspace: response.data
        });

    } catch (err) {
        console.error("Workspace info error:", err.response?.data || err.message);
        res.status(500).json({
            success: false,
            message: "Failed to get workspace info",
            error: err.response?.data?.message || err.message
        });
    }
});

// ===============================
// 4) CHECK WORKSPACE DOCUMENTS
// ===============================
app.get("/api/workspace-documents", async (req, res) => {
    try {
        const response = await axios.get(
            `${BASE_URL}/workspace/${WORKSPACE_ID}/documents`,
            {
                headers: {
                    Authorization: `Bearer ${API_KEY}`
                }
            }
        );

        res.json({
            success: true,
            documents: response.data,
            count: response.data.length
        });

    } catch (err) {
        console.error("Workspace documents error:", err.response?.data || err.message);
        res.status(500).json({
            success: false,
            message: "Failed to get workspace documents",
            error: err.response?.data?.message || err.message
        });
    }
});

app.listen(PORT, () => {
    console.log(`Server running at http://localhost:${PORT}`);
});