export async function injectFunctions(page) {
    console.log('injetou as funções');
    return await page.evaluate(async () => {
        window.WAPIWU = {};

        // Busca o metadata de um grupo
        window.WAPIWU.getGroupMetadata = async (chatId) => {
            return await require("WAWebDBGroupsGroupMetadata").getGroupMetadata(chatId)
        };

        // Busca os participantes de um grupo
        window.WAPIWU.getGroupParticipants = async (chatId) => {
            return await require("WAWebSchemaParticipant").getParticipantTable().get(chatId)
        };

        // Seta os grupos na variável global
        window.WAPIWU.setGroups = async (groups) => {
            window.WAPIWU.groups = [];

            // Busca as informações de todos os chats
            await require("WAWebSchemaChat").getChatTable().all().then((chats) => {
                chats.forEach(async (chat) => {
                    let groupMetadata = await window.WAPIWU.getGroupMetadata(chat.id);

                    // Verifica se é grupo
                    if(groupMetadata) {
                        let participants = await window.WAPIWU.getGroupParticipants(chat.id);
    
                        // Adiciona os participantes no metadata do grupo
                        groupMetadata.participants = participants;
    
                        // Adiciona o grupo na variável global
                        window.WAPIWU.groups.push(groupMetadata);
                    } 
                })
            })
        };

        // Busca todos os grupos
        window.WAPIWU.getAllGroups = async () => {
            try {
                await window.WAPIWU.setGroups();
                return {success: true, groups: window.WAPIWU.groups};
            } catch (error) {
                return {success: false, 'message': 'Erro ao buscar os grupos'};
            }
        };

        // Setar o título de um grupo
        window.WAPIWU.setGroupSubject = async (groupId, subject) => {
            try {
                var setJobConfigGroup = require("WAWebGroupModifyInfoJob")
                var WAWebWidFactoryLocal = require("WAWebWidFactory")
                var group = WAWebWidFactoryLocal.createWid(groupId);
    
                // Seta o título do grupo
                var response = await setJobConfigGroup.setGroupSubject(group, subject)

                return {success: true, 'message': 'Alterado com sucesso'};
            } catch (error) {
                return {success: false, 'message': 'Erro ao alterar', error: error};
            }
        };

        // Seta a descrição de um grupo
        window.WAPIWU.setGroupDescription = async (groupId, description) => {
            try {
                // Seta as variáveis
                var setJobConfigGroup = require("WAWebGroupModifyInfoJob");
                var WAWebWidFactoryLocal = require("WAWebWidFactory");
                var group = WAWebWidFactoryLocal.createWid(groupId);

                var c = await require("WAWebDBGroupsGroupMetadata").getGroupMetadata(groupId)
                await setJobConfigGroup.setGroupDescription(group, description, require("WAHex").randomHex(8), c.descId)

                return {success: true, 'message': 'Alterado com sucesso'};
            } catch (error) {
                return {success: false, 'message': 'Erro ao alterar'};
            }
        };

        /**
         * Setar uma propriedade de um grupo
         * 86400/24 h 604800/7
         * @param {*} groupId 
         * @param {*} type 
         * @param {*} value 
         */
        window.WAPIWU.setGroupProperty = async (groupId, type, value) => {
            try {
                const constantsType = require("WAWebGroupConstants");
                const types = {
                    1: constantsType.GROUP_SETTING_TYPE.ALLOW_NON_ADMIN_SUB_GROUP_CREATION,
                    2: constantsType.GROUP_SETTING_TYPE.ANNOUNCEMENT,
                    3: constantsType.GROUP_SETTING_TYPE.EPHEMERAL,
                    4: constantsType.GROUP_SETTING_TYPE.MEMBERSHIP_APPROVAL_MODE,
                    5: constantsType.GROUP_SETTING_TYPE.NO_FREQUENTLY_FORWARDED,
                    6: constantsType.GROUP_SETTING_TYPE.REPORT_TO_ADMIN_MODE,
                    7: constantsType.GROUP_SETTING_TYPE.RESTRICT,
                };

                var setJobConfigGroup = require("WAWebGroupModifyInfoJob")

                var WAWebWidFactoryLocal = require("WAWebWidFactory")

                var group = WAWebWidFactoryLocal.createWid(groupId);

                var response = await setJobConfigGroup.setGroupProperty(group, types[type], value)

                return {success: response == "SetPropertyResponseSuccess", 'message': 'Alterado com sucesso'};
            } catch (error) {
                return {success: false, 'message': 'Erro ao alterar'};
            }
        };

        // Enviar uma mensagem para um chat
        window.WAPIWU.sendTextMsgToChat = async (chatId, text) => {
            try {
                var sendMessageText = require("WAWebSendTextMsgChatAction")
                
                // Busca as informações de todos os grupos
                var webCollection = require('WAWebCollections')
                
                // Response
                var response = await sendMessageText.sendTextMsgToChat(webCollection.Chat.get(chatId), text, {})
                var success  = response.messageSendResult == "OK"

                return {success: success, 'message': (success ? 'Enviado com sucesso' : `Erro ao enviar: ${response}`), chatId: chatId, text: text, response: response};
            } catch (error) {
                return {success: false, 'message': `Erro ao enviar catch: ${error}`, chatId: chatId, text: text};
            }
        };

        // Cria um link de convite para um grupo
        window.WAPIWU.getGroupInviteLink = async (groupId) => {
            try {
                var codeInvite = await require("WAWebMexFetchGroupInviteCodeJob").fetchMexGroupInviteCode(groupId)

                return {success: true, 'message': 'Enviado com sucesso', link: `https://chat.whatsapp.com/${codeInvite}`};
            } catch (error) {
                return {success: false, 'message': 'Erro ao enviar catch', error: error, groupId: groupId};
            }
        };

        // Função que retorna as configurações para enviar o arquivo
        window.WAPIWU.getConfigsSend = async (prepRawMedia, caption = '') => {
            // Busca as informações de todos os grupos
            var collections = require('WAWebCollections')
            var chat = collections.Chat.get("120363298399150006@g.us")
            var dataUtil = require('WAWebMsgDataUtils')
            var msg  = await dataUtil.genOutgoingMsgData(chat, window.fileSend.type);
            
            var typesFile = {
                'image/jpeg': 'image',
                'image/png': 'image',
                'image/gif': 'gif',
                'video/webm': 'video',
                'video/mp4': 'video',
            }

            msg.body = prepRawMedia._mediaData.preview
            msg.filehash = prepRawMedia._mediaData.filehash
            msg.type = typesFile[window.fileSend.type]
            msg.agentId = undefined
            msg.isNewMsg = true
            msg.local = true
            msg.ack = 0
            msg.caption = caption
            msg.mentionedJidList = []
            msg.groupMentions = []
            msg.ephemeralSettingTimestamp = null
            msg.disappearingModeInitiator = "chat"
            msg.size = window.fileSend.size
            msg.isVcardOverMmsDocument = false
            msg.filename = undefined
            msg.mimetype = window.fileSend.type
            msg.isViewOnce = false
            msg.ctwaContext = undefined
            msg.ephemeralDuration = 604800
            msg.footer = undefined
            msg.forwardedFromWeb = undefined
            msg.forwardedNewsletterMessageInfo = undefined
            msg.forwardingScore = undefined
            msg.gifAttribution = undefined
            msg.height = 187
            msg.width = 198
            msg.streamingSidecar = undefined
            msg.isAvatar = undefined
            msg.isForwarded = undefined
            msg.isGif = undefined
            msg.messageSecret = undefined
            msg;multicast = undefined
            msg.pageCount = undefined
            msg.quotedMsg = undefined
            msg.quotedParticipant = undefined
            msg.quotedRemoteJid = undefined
            msg.quotedStanzaID = undefined
            msg.staticUrl = ""
            msg.subtype = undefined

            return msg
        }

        // Faz o envio do arquivo
        window.WAPIWU.sendFile = async (chatId, caption) => {
            try {
                // Cria o arquivo para envio
                var createFromData = await require("WAWebMediaOpaqueData").createFromData(window.fileSend, window.fileSend.type)
                var prepRawMedia   = await require("WAWebMedia").prepRawMedia(createFromData, {});
                var objMediaData   = prepRawMedia._mediaData;

                // Cria o objeto para envio
                objMediaData.set({mediaPrep: prepRawMedia})

                // Busca a informação do chat de envio
                var WAWebCollections = require('WAWebCollections')
                var chat             = WAWebCollections.Chat.get(chatId)
                
                // Envia o arquivo
                var configsSend = await window.WAPIWU.getConfigsSend(prepRawMedia, caption)
                var addMsg = require("WAWebMsgCollection").MsgCollection.add(configsSend)
                var response = await objMediaData.mediaPrep.sendToChat(chat, addMsg[0]);
                var success = response.messageSendResult == "OK";

                return {success: success, message: (success ? 'Arquivo enviado com sucesso' : 'Erro ao enviar o arquivo'), response: response};
            } catch (error) {
                return {success: false, message: 'Erro ao enviar o arquivo a', error: error};
            }
        }

        // Cria um novo grupo
        window.WAPIWU.createGroup = async (name, participants = []) => {
            try {
                // Ação que irá criar o grupo
                var createGroup = require("WAWebCreateGroupAction");

                // Configurações do grupo
                const configs = {
                    "title": name,
                    "ephemeralDuration": 0,
                    "restrict": false,
                    "announce": false,
                    "membershipApprovalMode": false,
                    "memberAddMode": false
                }
                
                // Cria o grupo
                const response = await createGroup.createGroup(configs, participants);
                const success = response.server == "g.us";

                // Busca as informações de um grupo
                const metadata = await require("WAWebDBGroupsGroupMetadata").getGroupMetadata(response._serialized);

                return {success: success, message: (success ? 'Grupo criado com sucesso' : 'Erro ao criar grupo'), metadata: metadata};
            } catch (error) {
                return {success: false, message: 'Erro ao criar grupo', error: error};
            }
        }

        // Desativa os autodownloads
        window.WAPIWU.disableAutoDownloads = () => {
            var permissionDownload = require('WAWebUserPrefsGeneral');

            // Desativa o download automático
            permissionDownload.setAutoDownloadPhotos(false);
            permissionDownload.setAutoDownloadAudio(false);
            permissionDownload.setAutoDownloadVideos(false);
            permissionDownload.setAutoDownloadDocuments(false);

            return true;
        }

        // Desativa todos autodownloads
        // window.WAPIWU.disableAutoDownloads();
    });
}
