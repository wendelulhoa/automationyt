window.WAPIWU = {};

// Guarda os eventos do whatsapp
window.WAPIWU.webhookEvents = {};

// Função para buscar informações de um grupo
window.WAPIWU.findGroupInfo = async (groupId) => {
    try {
        // Busca as informações do grupo
        let groupMetadata = await window.WAPIWU.getGroupMetadata(groupId);

        // Verifica se é grupo
        let participants = await window.WAPIWU.getGroupParticipants(groupId);

        // Adiciona os participantes no metadata do grupo
        groupMetadata.participants = participants;
        groupMetadata.inviteCode = (await window.WAPIWU.getGroupInviteLink(groupId)).link;

        return { success: true, message: 'Grupo encontrado com sucesso.', metadata: groupMetadata };
    } catch (error) {
        return { success: false, message: "Erro ao buscar as informações do grupo", error: error.message, groupId: groupId };
    }
};

// Busca o metadata de um grupo
window.WAPIWU.getGroupMetadata = async (chatId) => {
    return await require("WAWebDBGroupsGroupMetadata").getGroupMetadata(chatId);
};

// Busca os participantes de um grupo
window.WAPIWU.getGroupParticipants = async (chatId) => {
    return await require("WAWebSchemaParticipant")
        .getParticipantTable()
        .get(chatId);
};

// Seta os grupos na variável global
window.WAPIWU.setGroups = async () => {
    window.WAPIWU.groups = [];

    try {
        // Busca as informações de todos os chats
        const chats = await require("WAWebSchemaChat").getChatTable().all();

        // Usa Promise.all para lidar com as funções assíncronas
        await Promise.all(
            chats.map(async (chat) => {
                let groupMetadata = await window.WAPIWU.getGroupMetadata(chat.id);

                // Verifica se é grupo
                if (groupMetadata) {
                    let participants = await window.WAPIWU.getGroupParticipants(chat.id);

                    // Adiciona os participantes no metadata do grupo
                    groupMetadata.participants = participants;

                    // Adiciona o grupo na variável global
                    window.WAPIWU.groups.push(groupMetadata);
                }
            })
        );
    } catch (error) {
        console.error("Erro ao setar os grupos:", error);
    }
};

// Busca todos os grupos
window.WAPIWU.getAllGroups = async () => {
    try {
        await window.WAPIWU.setGroups();

        return { success: true, groups: window.WAPIWU.groups, message: "Grupos encontrados com sucesso" };
    } catch (error) {
        return { success: false, message: "Erro ao buscar os grupos", error: error.message };
    }
};

// Setar o título de um grupo
window.WAPIWU.setGroupSubject = async (groupId, subject) => {
    try {
        // Faz a requisição para setar o título
        const response = await require("WASmaxGroupsSetSubjectRPC").sendSetSubjectRPC({
            iqTo: groupId,
            subjectElementValue: subject
        });

        // Verifica se deu sucesso
        const success = response.name === "SetSubjectResponseSuccess";

        return { success: success, message: (success ? "Alterado com sucesso" : "Erro ao alterar"), response: response };
    } catch (error) {
        return { success: false, message: "Erro ao alterar", error: error.message };
    }
};

// Seta a descrição de um grupo
window.WAPIWU.setGroupDescription = async (groupId, description) => {
    try {
        // Busca o metadata do grupo
        var c = await require("WAWebDBGroupsGroupMetadata").getGroupMetadata(groupId);

        // Faz a requisição para setar a descrição
        const response = await require("WASmaxGroupsSetDescriptionRPC").sendSetDescriptionRPC({
            bodyArgs: {
                bodyElementValue: (description || " ")
            },
            iqTo: groupId,
            descriptionId: require("WAHex").randomHex(8),
            descriptionPrev: c.descId,
            hasDescriptionDeleteTrue: !1
        });

        // Verifica se deu sucesso
        const success = response.name == "SetDescriptionResponseSuccess"

        return { success: success, message: (success ? "Alterado com sucesso" : "Erro ao alterar"), response: response };
    } catch (error) {
        return { success: false, message: "Erro ao alterar", error: error.message };
    }
};

/**
 * Setar uma propriedade do grupo
 * 
 * @param {*} groupId
 * @param {*} property
 * @param {*} active
 */
window.WAPIWU.setGroupProperty = async (groupId, property, active) => {
    try {
        var configs = {};
        // Garante
        property =`${property}`;

        // Seta as configurações a serem alteradas
        switch (property) {
            case 'announcement': // Seta que somente os admins podem enviar mensagens
                configs = {
                    "hasAnnouncement": active,
                    "hasNotAnnouncement": !active,
                    "iqTo": groupId
                };
                break;
            case 'ephemeral': // Desativa as mensagens temporárias 
                configs = {
                    "hasNotEphemeral": true,
                    "iqTo": groupId
                };
                break;
            case 'restrict': // Seta que somente os admins podem editar o grupo
                configs = {
                    "hasLocked": active,
                    "hasUnlocked": !active,
                    "iqTo": groupId
                };
                break;
        }
        
        // Ativa as mensagens temporárias
        if(property == 'ephemeral' && active) {
            configs = {
                "hasNotEphemeral": false,
                "ephemeralArgs": {
                    "ephemeralExpiration": 604800
                },
                "iqTo": groupId
            };
        }

        // Faz a requisição para setar a propriedade
        const response = await require("WASmaxGroupsSetPropertyRPC").sendSetPropertyRPC(configs);

        // Verifica se deu sucesso
        const success = response.name == "SetPropertyResponseSuccess";

        return {
            success: success,
            message: (success ? "Alterado com sucesso" : "Erro ao alterar"),
            response: response
        };
    } catch (error) {
        return { success: false, message: "Erro ao alterar", error: error.message};
    }
};

// Busca o chat se não adiciona
window.WAPIWU.getChat = (chatId) => {
    try {
        // Seta o que será utilizado para adicionar os chats
        var WAWebWidFactoryLocal = require("WAWebWidFactory");
        var webCollection = require("WAWebCollections");

        // Busca o chat
        var chat = webCollection.Chat.get(chatId);

        // Adiciona o contato na lista de contatos.
        if(!chat) {
           chat = webCollection.Chat.gadd(WAWebWidFactoryLocal.createWid(chatId), {})
        }

        return chat;
    } catch (error) {
        return undefined;
    }
};

// Monta o link preview
window.WAPIWU.getLinkPreview = async (url) => {
    try {
        // Pega query
        var query = require("WAWebMexFetchPlaintextLinkPreviewJobQuery.graphql");

        // Pega a url
        var urlQuery = {
            "input": {
                "url": url
            }
        };

        // Faz a requisição
        var WAWebMexNativeClient = require('WAWebMexNativeClient');
        var response = await WAWebMexNativeClient.fetchQuery(query, urlQuery);
        var content  = response.xwa2_newsletter_link_preview;

        return {
            "matchedText": url,
            "title": content.title,
            "description": content.description,
            "richPreviewType": 0,
            "doNotPlayInline": true,
            "isLoading": false,
            "thumbnail": content.thumb_data
        };
    } catch (error) {
        return {
            "matchedText": url,
            "title": url,
            "description": url,
            "richPreviewType": 0,
            "doNotPlayInline": true,
            "isLoading": false,
            "thumbnail": undefined
        };
    }
}

// Monta a lista de mentions
window.WAPIWU.mountAllMention = async (chatId) => {
    // Busca os participantes do grupo
    var participants = await window.WAPIWU.getGroupParticipants(chatId);
    var WAWebWidFactoryLocal = require("WAWebWidFactory");
    
    // Inicializa a lista de mentions
    var mentions = [];

    // Monta a lista a ser mencionada
    participants.participants.forEach((contact) => {
        mentions.push(WAWebWidFactoryLocal.createWid(contact));   
    })

    return mentions;
}

// Enviar uma mensagem para um chat
window.WAPIWU.sendText = async (chatId, text, mention = false) => {
    try {
        var sendMessageText = require("WAWebSendTextMsgChatAction");

        // Envia o texto
        var response = await sendMessageText.sendTextMsgToChat(
            window.WAPIWU.getChat(chatId),
            text,
            {
                "linkPreview": null,
                "mentionedJidList": mention ? await window.WAPIWU.mountAllMention(chatId) : [],
                "groupMentions": [],
                "botMsgBodyType": null
            }
        );

        // Verifica se deu sucesso
        var success = response.messageSendResult == "OK";

        return {
            success: success,
            message: success
                ? "Enviado com sucesso"
                : `Erro ao enviar a mensagem`,
            chatId: chatId,
            response: response,
        };
    } catch (error) {
        return {
            success: false,
            message: `Erro ao enviar catch: ${error.message}`,
            chatId: chatId,
            error: error.message,
        };
    }
};

// Enviar um linkpreview para um chat
window.WAPIWU.sendLinkPreview = async (chatId, text, link) => {
    try {
        var sendMessageText = require("WAWebSendTextMsgChatAction");

        // Response
        var response = await sendMessageText.sendTextMsgToChat(
            window.WAPIWU.getChat(chatId),
            text,
            {
                "linkPreview": await window.WAPIWU.getLinkPreview(link),
                "mentionedJidList": [],
                "groupMentions": [],
                "botMsgBodyType": null
            }
        );
        var success = response.messageSendResult == "OK";

        return {
            success: success,
            message: success
                ? "Enviado com sucesso"
                : `Erro ao enviar o link`,
            chatId: chatId,
            response: response,
        };
    } catch (error) {
        return {
            success: false,
            message: `Erro ao enviar catch: ${error.message}`,
            error: error.message,
            chatId: chatId,
            response: null,
        };
    }
};

// Cria um link de convite para um grupo
window.WAPIWU.getGroupInviteLink = async (groupId) => {
    try {
        var codeInvite = await require("WAWebMexFetchGroupInviteCodeJob").fetchMexGroupInviteCode(
                groupId
            );

        // Verifica se o código foi gerado
        var success = codeInvite != null;

        return {
            success: success,
            message: (success ? "Link gerado com sucesso" : "Erro ao gerar link"),
            link: `https://chat.whatsapp.com/${codeInvite}`,
        };
    } catch (error) {
        return {
            success: false,
            message: "Erro ao enviar catch",
            error: error,
            groupId: groupId,
        };
    }
};

// Função que retorna as configurações para enviar o arquivo
window.WAPIWU.getConfigsSend = async (chatId, prepRawMedia, caption = "", fileSend) => {
    // Busca as informações de todos os grupos
    var collections = require("WAWebCollections");
    var chat = collections.Chat.get(chatId);
    var dataUtil = require("WAWebMsgDataUtils");
    var msg = await dataUtil.genOutgoingMsgData(chat, fileSend.type);

    var typesFile = {
        "image/jpeg": "image",
        "image/png": "image",
        "image/gif": "gif",
        "video/webm": "video",
        "video/mp4": "video",
        'audio/ogg': 'ogg',
        'image/webp': 'webp'
    };

    msg.body = prepRawMedia._mediaData.preview;
    msg.filehash = prepRawMedia._mediaData.filehash;
    msg.type = typesFile[fileSend.type];
    msg.agentId = undefined;
    msg.isNewMsg = true;
    msg.local = true;
    msg.ack = 0;
    msg.caption = caption;
    msg.mentionedJidList = [];
    msg.groupMentions = [];
    msg.ephemeralSettingTimestamp = null;
    msg.disappearingModeInitiator = "chat";
    msg.size = fileSend.size;
    msg.isVcardOverMmsDocument = false;
    msg.filename = undefined;
    msg.mimetype = fileSend.type;
    msg.isViewOnce = false;
    msg.ctwaContext = undefined;
    msg.ephemeralDuration = 604800;
    msg.footer = undefined;
    msg.forwardedFromWeb = undefined;
    msg.forwardedNewsletterMessageInfo = undefined;
    msg.forwardingScore = undefined;
    msg.gifAttribution = undefined;
    msg.height = 187;
    msg.width = 198;
    msg.streamingSidecar = undefined;
    msg.isAvatar = undefined;
    msg.isForwarded = undefined;
    msg.isGif = undefined;
    msg.messageSecret = undefined;
    msg.pageCount = undefined;
    msg.quotedMsg = undefined;
    msg.quotedParticipant = undefined;
    msg.quotedRemoteJid = undefined;
    msg.quotedStanzaID = undefined;
    msg.staticUrl = "";
    msg.subtype = undefined;

    return msg;
};

// Faz o envio do arquivo
window.WAPIWU.sendFile = async (chatId, caption, inputUsed) => {
    try {
        // Pega o arquivo
        var fileSend = document.querySelector(inputUsed).files[0];

        // Cria o arquivo para envio
        var createFromData = await require("WAWebMediaOpaqueData").createFromData(
            fileSend,
            fileSend.type
        );

        // Prepara o arquivo para envio
        var prepRawMedia = await require("WAWebMedia").prepRawMedia(
            createFromData,
            {}
        );
        var objMediaData = prepRawMedia._mediaData;

        // Cria o objeto para envio
        objMediaData.set({ mediaPrep: prepRawMedia });

        // Busca a informação do chat de envio
        var WAWebCollections = require("WAWebCollections");
        var chat = WAWebCollections.Chat.get(chatId);

        // Envia o arquivo
        var configsSend = await window.WAPIWU.getConfigsSend(
            chatId,
            prepRawMedia,
            caption,
            fileSend
        );

        // Envia a mensagem
        var addMsg = require("WAWebMsgCollection").MsgCollection.add(configsSend);
        var response = await objMediaData.mediaPrep.sendToChat(chat, addMsg[0]);
        var success = response.messageSendResult == "OK";

        return {
            success: success,
            message: success
                ? "Arquivo enviado com sucesso"
                : "Erro ao enviar o arquivo",
            response: response,
            chatId: chatId,
        };
    } catch (error) {
        return {
            success: false,
            message: "Erro ao enviar o arquivo a",
            error: error.message,
            chatId: chatId,
        };
    }
};

// Cria um novo grupo
window.WAPIWU.createGroup = async (name, participants = []) => {
    try {
        // Ação que irá criar o grupo
        var createGroup = require("WAWebCreateGroupAction");

        // Configurações do grupo
        const configs = {
            title: name,
            ephemeralDuration: 0,
            restrict: false,
            announce: false,
            membershipApprovalMode: false,
            memberAddMode: false,
        };

        // Cria o grupo
        const response = await createGroup.createGroup(configs, participants);
        const success = response.server == "g.us";

        return {
            success: success,
            message: success
                ? "Grupo criado com sucesso"
                : "Erro ao criar grupo",
            metadata: {
                id: response._serialized,
            },
        };
    } catch (error) {
        return { success: false, message: "Erro ao criar grupo", error: error.message };
    }
};

// Função de utilidade para pausar a execução
window.WAPIWU.sleep = async (ms) => {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Pega o qr code
window.WAPIWU.getQrCode = async () => {
    const maxWaitTime = 30000; // 30 segundos
    const interval = 1000; // 1 segundo
    let elapsedTime = 0;

    while (elapsedTime < maxWaitTime) {
        try {
            var path = `//*/div/div[2]/div[3]/div[1]/div/div/div[2]/div`;
            var elQrcode = document.evaluate(path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
            var qrCode = elQrcode ? elQrcode.getAttribute('data-ref') : null;

            // Retorna o qr code
            if (qrCode !== null) {
                return { success: true, qrCode: qrCode, message: 'QR gerado com sucesso' };
            }

            await window.WAPIWU.sleep(interval);
            elapsedTime += interval;
        } catch (error) {
            return { success: false, error: error.message, qrCode: null};
        }
    }

    return { success: false, error: 'QR code não encontrado em 30 segundos', qrCode: null };
}

// Desativa os autodownloads
window.WAPIWU.disableAutoDownloads = () => {
    var permissionDownload = require("WAWebUserPrefsGeneral");

    // Desativa o download automático
    permissionDownload.setAutoDownloadPhotos(false);
    permissionDownload.setAutoDownloadAudio(false);
    permissionDownload.setAutoDownloadVideos(false);
    permissionDownload.setAutoDownloadDocuments(false);

    return true;
};

// Desativa todos autodownloads
window.WAPIWU.disableAutoDownloads();

// Adiciona um inputfile no body
window.WAPIWU.addInputFile = (nameInput) => {
    var input = document.createElement('input');
    input.type = 'file';
    input.name = `${nameInput}`;
    input.dataset[`${nameInput}`] = `${nameInput}`
    input.dataset[`namedata`] = `${nameInput}`
    input.style.display = 'none'; // Ocultar o input
    document.body.appendChild(input);

    return { success: true, message: 'Input adicionado com sucesso' };
};

// Remove inputfile do body
window.WAPIWU.removeInputFile = (nameInput) => {
    var input = document.querySelector(`input[data-${nameInput}="${nameInput}"]`);
    input.remove();

    return { success: true, message: 'Input removido com sucesso' };
};

// Função para checar a conexão
window.WAPIWU.checkConnection = async () => {
    try {
        var WAWebStreamModel = require('WAWebStreamModel');
        var success = WAWebStreamModel.Stream.__x_displayInfo == 'NORMAL'

        return { success: success, message: success ? 'Conexão OK' : 'Erro na conexão', status: WAWebStreamModel.Stream.__x_displayInfo};
    } catch (error) {
        return { success: false, message: 'Erro na conexão', error: error.message, status: null};
    }
};

// Função para enviar enquete
window.WAPIWU.sendPoll = async (chatId, title, options, selectableCount = 0) => {
    try {
        // Monta as variaveis
        var poll = {
            name: title,
            options: options,
            selectableOptionsCount: selectableCount
        };
        
        // Envia a enquete
        const response = await require('WAWebPollsSendPollCreationMsgAction').sendPollCreation({ poll: poll, chat: window.WAPIWU.getChat(chatId), quotedMsg: undefined });

        // Verifica se deu sucesso
        const success = response[1].messageSendResult === "OK";

        return {success: success, message: (success ? "Enquete enviada com sucesso" : "Erro ao enviar enquete"), response: response, chatId: chatId};
    } catch (error) {
        return { success: false, message: 'Erro ao enviar enquete', error: error.message, chatId: chatId };
    }
};

// Função para enviar enquete
window.WAPIWU.sendAudio = async (chatId, inputUsed) => {
    try {
        // Pega o arquivo
        var fileSend = document.querySelector(inputUsed).files[0];

        // Busca as informações de todos os grupos
        var chat = window.WAPIWU.getChat(chatId);

        // Monta o MediaCollection
        var WAWebAttachMediaCollection = require('WAWebAttachMediaCollection')
        var WAWebAttachMediaCollection = new WAWebAttachMediaCollection({chatParticipantCount: chat.getParticipantCount()});

        // Prepara o arquivo para envio
        var files = [{
            "file": fileSend,
            "filename": `${window.WAPIWU.generateRandomCode(10)}.ogg`,
            "mimetype": "audio/ogg",
            "type": "audio"
        }]

        // Processa o arquivo
        await WAWebAttachMediaCollection.processAttachments(files, 1, chat)

        // Pega o media
        var media = WAWebAttachMediaCollection.getModelsArray()[0];
        media.mediaPrep._mediaData.type = 'ptt';
        
        // Envia o arquivo
        const response = await media.mediaPrep.sendToChat(chat, {})

        // Verifica se deu sucesso
        const success = response.messageSendResult === "OK";

        return {success: success, message: (success ? "Áudio enviado com sucesso" : "Erro ao enviar áudio"), response: response, chatId: chatId};
    } catch (error) {
        return { success: false, message: 'Erro ao enviar áudio', error: error.message, chatId: chatId };
    }
};

// Busca o número de telefone
window.WAPIWU.getPhoneNumber = async () => {
    try {
        var phoneNumber = require('WAWebUserPrefsMeUser').getMe().user;

        return { success: true, phoneNumber: phoneNumber, message: 'Número de telefone encontrado' };
    } catch (error) {
        return { success: false, message: 'Erro ao buscar o número de telefone', error: error.message };
    }
};

// Inicia a sessão
window.WAPIWU.startSession = async () => {
    try {
        return { success: true, message: 'Sessão iniciada com sucesso' };
    } catch (error) {
        return { success: false, message: 'Erro ao iniciar a sessão', error: error.message };
    }
};

// Função para promover um participante
window.WAPIWU.promoteParticipants = async (groupId, number, isCommunity = false) => { 
    try {
        // Seta a variável de sucesso/response
        var success  = false;
        var response = null;

        // promover o participante grupos
        if(!isCommunity) {
            response = await require("WASmaxGroupsPromoteDemoteRPC").sendPromoteDemoteRPC({
                promoteArgs: {
                  participantArgs: [{participantJid: number}]
                },
                iqTo: groupId,
            });

            // Verifica se deu sucesso
            success = response.value.promoteParticipant.length > 1 && response.value.promoteParticipant[0].error == null || response.value.promoteParticipant.length == 0;
        }
        // promover o participante comunidade
        else {
            response = await require("WASmaxGroupsPromoteDemoteAdminRPC").sendPromoteDemoteAdminRPC({
                promoteArgs: {
                  participantArgs: [{participantJid: number}]
                },
                iqTo: groupId,
            });

            // verifica se deu sucesso
            success = response.value.adminParticipant.length > 1 && response.value.adminParticipant[0].error == undefined || response.value.adminParticipant.length == 0
        }

        return { success: success, message: (success ? 'Participante promovido com sucesso' : 'Erro ao promover o participante'), isCommunity: isCommunity, response: response, number: number, groupId: groupId };
    } catch (error) {
        return { success: success, message: 'Erro ao promover o participante', error: error.message, isCommunity: isCommunity, number: number, groupId: groupId };
    }
};

// Função para despromover um participante
window.WAPIWU.demoteParticipants = async (groupId, number, isCommunity = false) => { 
    try {
        // Seta a variável de sucesso
        var success  = false;
        var response = null;

        // Despromove o participante
        if(!isCommunity) {
            response = await require("WASmaxGroupsPromoteDemoteRPC").sendPromoteDemoteRPC({
                promoteArgs: {
                    participantArgs: [{participantJid: number}]
                },
                iqTo: groupId,
            });

            // Verifica se deu sucesso
            success = response.value.demoteParticipant[0].error == undefined
        }
        // Despromove o participante comunidade
        else {
            response = await require("WASmaxGroupsPromoteDemoteAdminRPC").sendPromoteDemoteAdminRPC({
                demoteArgs: {
                  participantArgs: [{participantJid: number}]
                },
                iqTo: groupId,
            });
            // verifica se deu sucesso
            success = response.value.adminParticipant.length > 1 && response.value.adminParticipant[0].error == undefined || response.value.adminParticipant.length == 0
        }

        return { success: success, message: (success ? 'Participante despromovido com sucesso' : 'Erro ao despromover o participante'), isCommunity: isCommunity, response: response, number: number, groupId: groupId };
    } catch (error) {
        return { success: false, message: 'Erro ao despromover o participante', error: error.message, isCommunity: isCommunity, number: number, groupId: groupId };
    }
};

// Função para promover um participante
window.WAPIWU.addParticipant = async (groupId, number) => { 
    try {
        // Adiciona o participante
        const response = await require("WASmaxGroupsAddParticipantsRPC").sendAddParticipantsRPC({
            participantArgs: [{participantJid: number}],
            iqTo: groupId,
        });

        // Pega o retorno
        const addParticipantsParticipantMixins = response.value.addParticipant[0].addParticipantsParticipantAddedOrNonRegisteredWaUserParticipantErrorLidResponseMixinGroup.value.addParticipantsParticipantMixins;

        // Verifica se deu sucesso
        const success = addParticipantsParticipantMixins == null || addParticipantsParticipantMixins.value.error == '409' ;

        // Verifica se deu erro
        if(addParticipantsParticipantMixins != null && !success) {
            return { success: false, message: 'Erro ao adicionar o participante', error: addParticipantsParticipantMixins.name , response: response, status: addParticipantsParticipantMixins.value.error};
        }

        return { success: success, message: (success ? 'Participante Adicionado com sucesso' : 'Erro ao adicionar o participante'), response: response, number: number, groupId: groupId, status: 200};
    } catch (error) {
        return { success: false, message: 'Erro ao adicionar o participante catch', error: error.message, number: number, groupId: groupId, status: 500};    
    }
};

// Função para promover um participante
window.WAPIWU.removeParticipant = async (groupId, number) => { 
    try {
        // Seta as variáveis
        var groupInfo = require("WAWebGroupMetadataCollection").assertGet(groupId);

        // Pega o contato
        var contact     = groupInfo.participants.get(number);

        // Verifica se o participante é admin
        if(!contact) {
            return { success: true, message: 'Participante não está adicionado'};      
        }

        // Remove participante
        const response = await require("WASmaxGroupsRemoveParticipantsRPC").sendRemoveParticipantsRPC({
            participantArgs: [{participantJid: number}],
            iqTo: groupId,
            hasRemoveLinkedGroupsTrue: !1,
        });

        // Verifica se deu sucesso
        const success = response.value.removeParticipant[0].participantNotInGroupOrParticipantNotAllowedOrParticipantNotAcceptableOrRemoveParticipantsLinkedGroupsServerErrorMixinGroup == null;

        return { success: success, message: (success ? 'Participante removido com sucesso' : 'Erro ao remover o participante'), response: response, number: number, groupId: groupId};
    } catch (error) {
        return { success: false, message: 'Erro ao remover o participante', error: error.message, number: number, groupId: groupId };
    }
};

// Seta os eventos para enviar para o webhook
window.WAPIWU.setWebhookEvent = async function (events) {
    events.forEach(event => {
        console.log('event', event);
        switch(event.subtype) {
            case "leave":
            case "invite":
            case "message":
                window.WAPIWU.webhookEvents[event.id.id] = event;
                break;
        }
    });
};

// Gera um código sempre aleatório
window.WAPIWU.generateRandomCode = (length = 22) => {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; // Conjunto de caracteres permitidos
    let code = '';
  
    for (let i = 0; i < length; i++) {
      // Seleciona um caractere aleatório do conjunto
      const randomIndex = Math.floor(Math.random() * characters.length);
      code += characters[randomIndex]; // Adiciona o caractere selecionado ao código
    }
  
    return code;
}

// Envia um vcard
window.WAPIWU.sendVcard = async (chatId, title, contact) => {
    try {
        // Busca o chat
        var chat = window.WAPIWU.getChat(chatId);

        // Monta as informações de envio
        var dataUtil = require('WAWebMsgDataUtils') 
        var msg = await dataUtil.genOutgoingMsgData(chat, 'text/vcard');

        // Remove código anterior e adiciona o novo
        msg.id._serialized = msg.id._serialized.replace(msg.id.id, ':newcode');
        msg.id.id = window.WAPIWU.generateRandomCode(22);
        msg.id._serialized = msg.id._serialized.replace(':newcode', msg.id.id);

        // Monta o vcard
        const msgData = {
            "type": "vcard",
            "vcardFormattedName": title,
            "body": `BEGIN:VCARD\nVERSION:3.0\nN:;${title};;;\nFN:${title}\nTEL;type=CELL;waid=${contact}:${contact}\nEND:VCARD`,
            "ack": 0,
            "from": msg.from,
            "id": msg.id,
            "local": true,
            "isNewMsg": true,
            "t": msg.t,
            "to": msg.to,
            "ephemeralDuration": 0
        }

        // Instância para o envio
        var WAWebSendMsgChatAction = require('WAWebSendMsgChatAction')
        const response = await WAWebSendMsgChatAction.addAndSendMsgToChat(chat, msgData);
        const result  = await response[1];
        const success = result.messageSendResult === "OK";

        return {success: success, message: (success ? "Vcard enviado com sucesso" : "Erro ao enviar vcard"), response: result, chatId: chatId};
    } catch (error) {
        return { success: false, message: 'Erro ao enviar vcard catch', error: error.message, line2: error, response: null, chatId: chatId };
    }
}

// Transforma o arquivo em base64
window.WAPIWU.fileToBase64 = async (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader(); // Cria uma instância do FileReader

        // Define a função de callback para quando a leitura do arquivo estiver concluída
        reader.onload = () => {
            resolve(reader.result); // Retorna a string Base64 com o tipo MIME
        };

        // Define a função de callback para erros
        reader.onerror = (error) => {
            reject(error); // Rejeita a promessa com o erro
        };

        reader.readAsDataURL(file); // Inicia a leitura do arquivo como uma URL de dados (Base64)
    });
};

// Redimensiona a imagem
window.WAPIWU.resizeImage = async (file, sizes) => {
    const reader = new FileReader();
    
    // Ler o arquivo de imagem como Data URL usando Promises
    const dataUrl = await new Promise((resolve) => {
        reader.onload = (event) => resolve(event.target.result);
        reader.readAsDataURL(file);
    });

    const img = new Image();
    img.src = dataUrl;

    await new Promise((resolve) => (img.onload = resolve));

    const resizedFiles = await Promise.all(
        sizes.map((size) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Calcula a nova largura e altura mantendo a proporção
            let width = img.width;
            let height = img.height;

            if (width > height) {
                if (width > size) {
                    height *= size / width;
                    width = size;
                }
            } else {
                if (height > size) {
                    width *= size / height;
                    height = size;
                }
            }

            // Define o tamanho do canvas
            canvas.width = width;
            canvas.height = height;

            // Desenha a imagem redimensionada no canvas
            ctx.drawImage(img, 0, 0, width, height);

            // Determina o novo tipo de arquivo como JPEG
            const outputType = file.type === 'image/png' ? 'image/jpeg' : file.type;

            // Converte o canvas para Blob e cria um arquivo redimensionado
            return new Promise((resolve) =>
                canvas.toBlob(
                    (blob) => {
                        const resizedFile = new File(
                            [blob], 
                            `${size}x${size}_${file.name.replace(/\.[^.]+$/, '.jpg')}`, // Substitui a extensão por .jpg
                            {
                                type: outputType,
                                lastModified: Date.now(),
                            }
                        );
                        resolve(resizedFile);
                    },
                    outputType
                )
            );
        })
    );

    return resizedFiles;
};

// Altera a foto de um grupo
window.WAPIWU.changeGroupPhoto = async (chatId, inputUsed) => {
    try {
        // Pega o arquivo
        var fileSend = document.querySelector(inputUsed).files[0];

        // Redimensiona a imagem
        var filesResized = await window.WAPIWU.resizeImage(fileSend, [96, 640]);

        // Transforma o arquivo em base64
        var file96  = await window.WAPIWU.fileToBase64(filesResized[0]);
        var file640 = await window.WAPIWU.fileToBase64(filesResized[1]);

        // Cria o wid do grupo
        var WAWebWidFactoryLocal = require("WAWebWidFactory");
        var groupWid = WAWebWidFactoryLocal.createWid(chatId);

        // Altera a foto do grupo
        var response = await require("WAWebContactProfilePicThumbBridge").sendSetPicture(groupWid, file96, file640);
        const success = response.status == 200;

        return {
            success: success,
            message: (success ? "Foto alterada com sucesso" : "Erro ao alterar a foto"),
            response: response,
            chatId: chatId,
        };
    } catch (error) {
        return {
            success: false,
            message: "Erro ao alterar a foto",
            error: error.message,
            chatId: chatId,
        };
    }
};

// Altera a foto de um grupo
window.WAPIWU.removeGroupPhoto = async (chatId) => {
    try {
        // Cria o wid do grupo
        var WAWebWidFactoryLocal = require("WAWebWidFactory");
        var groupWid = WAWebWidFactoryLocal.createWid(chatId);

        // Altera a foto do grupo
        var response = await require("WAWebContactProfilePicThumbBridge").requestDeletePicture(groupWid);
        const success = response.status == 200;

        return {
            success: success,
            message: (success ? "Foto removida com sucesso" : "Erro ao remover a foto"),
            response: response,
            chatId: chatId,
        };
    } catch (error) {
        return {
            success: false,
            message: "Erro ao remover a foto",
            error: error.message,
            chatId: chatId,
        };
    }
};

// Verifica se o número é válido ou não
window.WAPIWU.checkNumber = async (number) => {
    try {
        // Cria o wid do contato
        var WAWebWidFactoryLocal = require("WAWebWidFactory");
        var contactWid = WAWebWidFactoryLocal.createWid(number);

        // Verifica se o número é válido
        var isValid = await require("WAWebQueryExistsJob").queryWidExists(contactWid);
        var success = isValid == null ? false : true;

        return { success: success, message: (success ? "Número válido" : "Número inválido") };
    } catch (error) {
        return { success: false, message: "Erro ao verificar o número", error: error.message };
    }
};

// Função para fazer logout do whatsapp
window.WAPIWU.disconnect = async () => {
    try {
        // Faz o logout
        await require("WAWebSocketModel").Socket.logout();

        return { success: true, message: "Logout feito com sucesso" };
    } catch (error) {
        return { success: false, message: "Erro ao fazer logout", error: error.message };
    }
};

// Função para apagar uma mensagem
window.WAPIWU.deleteMessage = async function(chatId, msgId) {
   try {
        // Pega a mensagem a ser apagada 
        var WAWebMsgCollection = require("WAWebMsgCollection");
        var msg                = WAWebMsgCollection.MsgCollection.get(msgId)

        // Busca a informação do chat de envio
        var WAWebCollections = require("WAWebCollections");
        var chat = WAWebCollections.Chat.get(chatId);
            
        // Apaga a mensagem
        require('WAWebCmd').Cmd.sendRevokeMsgs(chat, {
                "type": "message",
                "list": [msg]
            }, {"clearMedia": true});
        const success = true;

        // Verifica se a mensagem foi apagada
        return { success: success, message: (success ? 'Mensagem apagada com sucesso' : 'Erro ao apagar a mensagem') };
   } catch (error) {
        return { success: false, message: 'Erro ao apagar a mensagem', error: error.message };
   }
};

// Criar nova comunidade
window.WAPIWU.createCommunity = async (name) => {
    try {
        // Ação que irá criar o grupo
        var createCommunity = require("WAWebGroupCommunityJob");

        // Configurações do grupo
        const configs = {
            "name": name,
            "desc": "",
            "closed": true,
            "hasAllowNonAdminSubGroupCreation": true,
            "shouldCreateGeneralChat": false
        };

        // Cria o grupo
        const response = await createCommunity.sendCreateCommunity(configs);
        const success = response.wid.server == "g.us";

        return {
            success: success,
            message: success
                ? "Comunidade criada com sucesso"
                : "Erro ao criar comunidade",
            metadata: {
                id: response.wid._serialized,
            },
            response: response
        };
    } catch (error) {
        return { success: false, message: "Erro ao criar comunidade catch", error: error.message, response: null, metadata: null};
    }
};

// Adiciona as funções customizadas que substitui as originais do webwhatsapp
@include('whatsapp-functions.injected-functionscustom-wa')
