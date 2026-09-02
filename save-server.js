const express = require('express');
const fs = require('fs');
const path = require('path');
const app = express();
app.use(express.json({ limit: '100mb' }));
app.use((req, res, next) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.sendStatus(200);
  next();
});

// Servir archivos estáticos del frontend
app.use(express.static(path.join(__dirname)));

const filePath = path.join(__dirname, 'data', 'colegio_santander.json');

app.post('/save', (req, res) => {
  const data = req.body;
  try {
    // Crear copia de seguridad con timestamp antes de sobrescribir
    try {
      if (fs.existsSync(filePath)) {
        const backupName = `colegio_santander.backup.${Date.now()}.json`;
        const backupPath = path.join(path.dirname(filePath), backupName);
        fs.copyFileSync(filePath, backupPath);
        console.log('Backup created at', backupPath);
      }
    } catch (bErr) {
      console.error('Error creating backup:', bErr);
    }

    fs.writeFileSync(filePath, JSON.stringify(data, null, 2), 'utf8');
    console.log('Saved to', filePath);
    res.json({ ok: true, path: filePath });
  } catch (err) {
    console.error('Error saving file:', err);
    res.status(500).json({ ok: false, error: err.message });
  }
});

const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`Save server listening on http://localhost:${port}`));
