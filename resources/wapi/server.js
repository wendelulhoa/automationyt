import express from 'express';
import { getQrcode } from './whatsapp-functions/getQrcode.js';
import { getAllGroups } from './whatsapp-functions/group/getAllGroups.js';
import { sendText } from './whatsapp-functions/chat/sendText.js';
import cors from 'cors';
const app = express();
const port = 3001;

app.use(express.json());
app.use(cors()); // Isso permitirá todas as origens. Para segurança, especifique as origens permitidas.

// Rota com sessionid no meio da URL
app.get('/api/:sessionid/getqrcode', async (req, res) => {
  const sessionid = req.params.sessionid;
  try {
    // Busca o qrcode
    const qrCode = await getQrcode(sessionid);

    res.json({ success: true, code: qrCode });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Erro ao processar a requisição' });
  }
});

app.get('/api/:sessionid/getallgroups', async (req, res) => {
  const sessionid = req.params.sessionid;

  try {
    // Busca os grupos
    const groups = await getAllGroups(sessionid);

    res.json({ success: true, groups });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Erro ao processar a requisição' });
  }
});

// app.post('/api/:sessionid/setgroupsubject', async (req, res) => {
//   const sessionid = req.params.sessionid;
//   const { title, groupId } = req.body;

//   try {
//     // Seta o título do grupo
//     const response = await setGroupSubject(sessionid, title, groupId);

//     res.json(response);
//   } catch (error) {
//     res.status(500).json({ success: false, message: 'Erro ao processar a requisição' });
//   }
// });

// app.post('/api/:sessionid/setgroupdescription', async (req, res) => {
//   const sessionid = req.params.sessionid;
//   const { description, groupId } = req.body;

//   try {
//     // Seta a descrição do grupo
//     const response = await setGroupDescription(sessionid, description, groupId);

//     res.json(response);
//   } catch (error) {
//     res.status(500).json({ success: false, message: 'Erro ao processar a requisição' });
//   }
// });

// app.post('/api/:sessionid/setgroupparticipants', async (req, res) => {
//   const sessionid = req.params.sessionid;
//   const { participants, groupId } = req.body;

//   try {
//     // Seta os participantes do grupo
//     const response = await setGroupParticipants(sessionid, participants, groupId);

//     res.json(response);
//   } catch (error) {
//     res.status(500).json({ success: false, message: 'Erro ao processar a requisição' });
//   }
// });

app.post('/api/:sessionid/sendtext', async (req, res) => {
  const sessionid = req.params.sessionid;
  const { chatid, text } = req.body;

  try {
    // Envia a mensagem
    const success = await sendText(sessionid, chatid, text);

    res.json({ success: success});
  } catch (error) {
    res.status(500).json({ success: false, message: `Erro ao processar a requisição:${error}` });
  }
});

// Iniciar o servidor
app.listen(port, () => {
  console.log(`Servidor rodando em http://localhost:${port}`);
});