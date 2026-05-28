<?php
/*
|--------------------------------------------------------------------------
| Zestex - Social Network Platform.
|--------------------------------------------------------------------------
| Based on: Zestex - The Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav
|--------------------------------------------------------------------------
| Branded by: Vicky Bedardi Yadav
| E-mail: vicktbedardi9@gmail.com
|--------------------------------------------------------------------------
| Copyright (c) Flip Basket Pvt Ltd. All rights reserved. 
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\Api\User\Chat;

use Exception;
use Throwable;
use App\Models\Chat;
use App\Models\User;
use App\Support\Num;
use App\Rules\X\XRule;
use App\Models\Message;
use App\Models\Product;
use App\Models\HiddenChat;
use App\Models\JobListing;
use Illuminate\Support\Str;
use App\Enums\Chat\ChatType;
use Illuminate\Http\Request;
use App\Constants\Relationship;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Events\User\Chat\MessageReadEvent;
use App\Services\Reaction\ReactionService;
use Illuminate\Database\Eloquent\Collection;
use App\Events\User\Chat\MessageDeletedEvent;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Events\User\Chat\MessageReceivedEvent;
use App\Actions\Chat\MessageGlobalDeleteAction;
use App\Actions\Chat\MessagesLocalDeleteAction;
use App\Http\Resources\User\Chat\ChatCollection;
use App\Http\Resources\User\Chat\MessageCollection;
use App\Http\Resources\User\Chat\ParticipantCollection;
use App\Http\Resources\User\Chat\RequestCollection;
use App\Http\Resources\User\Timeline\ReactionCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Resources\User\Overview\UserOverviewResource;
use App\Models\ChatInvite;
use App\Notifications\User\Chat\MessageReceivedNotification;
use App\Jobs\ProcessAiChatReply;
use App\Models\MessageAttachment;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    use SupportsApiResponses,
        AuthorizesRequests;

    private $unreadCount = 0;

    public function getChats()
    {
        $chatsHistory = Chat::chatsHistory()->with(['interlocutor', 'group', 'lastMessage'])->latest('last_activity')->get();

        $chatsHistory = $chatsHistory->filter(function ($chat) {
            if ($chat->type->isDirect()) {
                $interlocutor = $chat->interlocutor?->user;
                if ($interlocutor) {
                    return ! me()->isBlockedWith($interlocutor);
                }
            }

            return true;
        })->values();

        return $this->responseSuccess([
            'data' => ChatCollection::make($chatsHistory)
        ]);
    }

    public function getArchive()
    {
        $chatsHistory = Chat::chatsArchive()->with(['interlocutor', 'group', 'lastMessage'])->latest('last_activity')->get();

        $chatsHistory = $chatsHistory->filter(function ($chat) {
            if ($chat->type->isDirect()) {
                $interlocutor = $chat->interlocutor?->user;
                if ($interlocutor) {
                    return ! me()->isBlockedWith($interlocutor);
                }
            }

            return true;
        })->values();

        return $this->responseSuccess([
            'data' => ChatCollection::make($chatsHistory)
        ]);
    }

    public function getChatRequests()
    {
        $chatRequests = ChatInvite::pending()->where('receiver_id', me()->id)->with(['sender', 'chat.group'])->get();

        return $this->responseSuccess([
            'data' => RequestCollection::make($chatRequests)
        ]);
    }

    public function getChatRequestsCount()
    {
        $count = ChatInvite::pending()->where('receiver_id', me()->id)->count();

        return $this->responseSuccess([
            'data' => [
                'count' => $count,
            ]
        ]);
    }

    public function getUnreadCount()
    {
        // TODO: Optimize this query. Make it more efficient.

        $participatedChats = Chat::participatedChats()->get();

        $participatedChats->each(function ($chatData) {
            $participantData = $chatData->participants()->where('user_id', me()->id)->first();

            if($participantData) {
                $this->unreadCount += $chatData->messages()
                    ->whereNot('user_id', me()->id)
                    ->where('id', '>', $participantData->last_read_message_id)
                    ->count();
            }
        });

        return $this->responseSuccess([
            'data' => [
                'formatted' => Num::abbreviate($this->unreadCount),
                'raw' => $this->unreadCount
            ]
        ]);
    }

    public function markAsRead(string $chatId)
    {
        if(Str::isUuid($chatId)) {

            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                $userParticipant = $chatData->participants()->where('user_id', me()->id)->first();
                $lastMessageData = $chatData->messages()->latest()->first();
                $statusUpdated = false;
                
                if($lastMessageData && $userParticipant) {
                    if($userParticipant->last_read_message_id < $lastMessageData->id) {
                        $statusUpdated = true;
                        $userParticipant->update([
                            'last_read_message_id' => $lastMessageData->id,
                            'last_read_at' => now()
                        ]);

                        try {
                            event(new MessageReadEvent([
                                'chat_uuid' => $chatId,
                                'user_id' => me()->id,
                                'last_read_message_id' => $lastMessageData->id
                            ]));
                        } catch (Throwable $th) {
                            // Pass
                        }
                    }
                }
    
                return $this->responseSuccess([
                    'data' => [
                        'status_updated' => $statusUpdated
                    ]
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Chat', $chatId);
    }

    public function getChatParticipants(string $chatId)
    {
        if(Str::isUuid($chatId)) {
            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                $chatParticipants = $chatData->participants()->with('user:id,first_name,last_name,username,avatar,caption,last_active,verified')->take(7)->get();
    
                return $this->responseSuccess([
                    'data' => ParticipantCollection::make($chatParticipants)
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Chat', $chatId);
    }

    public function getChatMessages(Request $request, string $chatId)
    {
        if(Str::isUuid($chatId)) {
            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                if ($chatData->type->isDirect()) {
                    $interlocutor = $chatData->participants()->whereNot('user_id', me()->id)->first();
                    if ($interlocutor && $interlocutor->user && me()->isBlockedWith($interlocutor->user)) {
                        return $this->responseError([
                            'message' => 'This chat is not available.',
                            'errors' => [
                                'chat_id' => ['This chat is not available.']
                            ]
                        ], 403);
                    }
                }

                $chatMessages = $chatData->messages()->excludeDeleted()->with([
                    'reactions',
                    'attachments',
                    'participant',
                    'user:id,first_name,last_name,username,avatar,verified',
                    'parent.user:id,first_name,last_name,username,verified',
                    'parent.participant',
                    'linkSnapshot'
                ])->latest()->take(30)->get();

                return $this->responseSuccess([
                    'data' => MessageCollection::make($chatMessages->reverse())
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Chat', $chatId);
    }

    public function createChat(Request $request)
    {
        $userId = $request->integer('user_id');
        
        $userData = User::active()->excludeSelf()->find($userId);

        if(empty($userData)) {
            return $this->responseResourceNotFoundError('User', $userId);
        }
        else if (me()->isBlockedWith($userData)) {
            return $this->responseError([
                'message' => 'You cannot start a chat with this user.',
                'errors' => [
                    'user_id' => ['You cannot start a chat with this user.']
                ]
            ], 403);
        }
        else {
            $chatData = $this->initiateChat($userId);
            
            return $this->responseSuccess([
                'data' => [
                    'chat_id' => $chatData->chat_id
                ]
            ]);
        }
    }

    public function launchChat(Request $request)
    {
        $userId = $request->integer('user_id');
        
        $userData = User::active()->excludeSelf()->find($userId);

        if(empty($userData)) {
            return $this->responseResourceNotFoundError('User', $userId);
        }
        else if (me()->isBlockedWith($userData)) {
            return $this->responseError([
                'message' => 'You cannot start a chat with this user.',
                'errors' => [
                    'user_id' => ['You cannot start a chat with this user.']
                ]
            ], 403);
        }
        else {
            $chatData = $this->initiateChat($userId);

            return $this->responseSuccess([
                'data' => [
                    'interlocutor' => UserOverviewResource::make($userData),
                    'chat_id' => $chatData->chat_id,
                    'validation_rules' => [
                        'content' => config('chat.message.validation.content')
                    ]
                ]
            ]);
        }
        
    }

    public function getChatData(Request $request, string $chatId)
    {
        if(Str::isUuid($chatId)) {
            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                $participantsCount = $chatData->participants()->count();
                $isParticipant = $chatData->isParticipant(me()->id);

                $chatInfo = [
                    'type' => $chatData->type->value,
                    'is_group' => $chatData->type->isGroup(),
                    'chat_id' => $chatData->chat_id,
                    'meta' => [
                        'is_participant' => $isParticipant,
                        'is_archived' => $chatData->isArchived(me()->id)
                    ],
                    'date' => [
                        'timestamp' => $chatData->created_at->getTimestamp(),
                        'iso' => $chatData->created_at->getIso(),
                    ],
                    'chat_info' => [
                        'participants_count' => [
                            'raw' => $participantsCount,
                            'formatted' => Num::abbreviate($participantsCount)
                        ],
                        'verified' => false,
                    ],
                    'relations' => [
                        'participants' => []
                    ]
                ];

                // Check if the user is a participant and the chat is a direct chat.

                if ($chatData->type == ChatType::DIRECT) {
                    $interlocutorData = $chatData->interlocutor;
                    
                    $interlocutorData = (empty($interlocutorData)) ? null : $interlocutorData->user;

                    if ($interlocutorData && me()->isBlockedWith($interlocutorData)) {
                        return $this->responseError([
                            'message' => 'This chat is not available.',
                            'errors' => [
                                'chat_id' => ['This chat is not available.']
                            ]
                        ], 403);
                    }

                    $chatInfo['relations']['participants'] = $chatData
                        ->participants()
                        ->whereNot('user_id', me()->id)
                        ->select([
                            'user_id',
                            'last_read_message_id',
                            'last_read_at',
                        ])->get()->toArray();

                    // TODO: Add deleted user support.

                    $chatInfo['chat_info']['id'] = $interlocutorData->id;
                    $chatInfo['chat_info']['name'] = $interlocutorData->name;
                    $chatInfo['chat_info']['username'] = $interlocutorData->username;
                    $chatInfo['chat_info']['avatar_url'] = $interlocutorData->avatar_url;
                    $chatInfo['chat_info']['description'] = $interlocutorData->bio;
                    $chatInfo['chat_info']['verified'] = $interlocutorData->isVerified();
                    $chatInfo['chat_info']['followers_count'] = [
                        'raw' => $interlocutorData->followers_count,
                        'formatted' => Num::abbreviate($interlocutorData->followers_count)
                    ];
                    $chatInfo['chat_info']['last_active'] = [
                        'raw' => $interlocutorData->getLastActive()->getTimestamp(),
                        'formatted' => $interlocutorData->getLastActive()->getCalendar()
                    ];
                    $chatInfo['chat_info']['meta'] = [
                        'relationship' => [
                            Relationship::FOLLOW_GROUP => [
                                Relationship::FOLLOWED_BY => $interlocutorData->isFollowing(me()),
                            ]
                        ]
                    ];
                }
                
                else if ($chatData->type == ChatType::GROUP) {
                    $groupData = $chatData->group;

                    $chatInfo['chat_info']['id'] = $groupData->id;
                    $chatInfo['chat_info']['name'] = $groupData->name;
                    $chatInfo['chat_info']['avatar_url'] = $groupData->avatar_url;
                    $chatInfo['chat_info']['verified'] = $groupData->isVerified();
                    $chatInfo['chat_info']['description'] = $groupData->description;
                }

                return $this->responseSuccess([
                    'data' => $chatInfo
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Chat', $chatId);
    }

    public function sendMessage(Request $request)
    {   
        $validator = Validator::make([
            'chat_id' => $request->get('chat_id'),
            'content' => $request->get('content'),
            'parent_id' => $request->get('parent_id'),
            'attachments' => $request->array('attachments', [])
        ], [
            'chat_id' => ['required', 'uuid'],
            'parent_id' => ['nullable', 'integer'],
            'content' => ['nullable', 'string', 'min:1', XRule::join('max', config('chat.message.validation.content.max'))],
            'attachments' => ['nullable', 'array', 'max:' . config('chat.attachments.max_count', 5)],
            'attachments.*' => ['integer']
        ]);

        if($validator->passes()) {
            $chatId = $request->get('chat_id');
            $messageContent = $request->get('content');
            $parentId = $request->integer('parent_id');
            $attachmentIds = collect($request->array('attachments', []))->filter()->unique()->values();
            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                if ($attachmentIds->isEmpty() && empty($messageContent)) {
                    return $this->responseValidationError([
                        'message' => 'Content or attachments are required.',
                        'errors' => [
                            'content' => ['Content or attachments are required.']
                        ]
                    ]);
                }

                if ($chatData->type->isDirect()) {
                    $interlocutor = $chatData->participants()->whereNot('user_id', me()->id)->first();
                    if ($interlocutor && $interlocutor->user && me()->isBlockedWith($interlocutor->user)) {
                        return $this->responseError([
                            'message' => 'You cannot message this user.',
                            'errors' => [
                                'chat_id' => ['You cannot message this user.']
                            ]
                        ], 403);
                    }
                }

                $participantData = $chatData->participants()->where('user_id', me()->id)->first();
                $otherParticipants = $chatData->participants()->whereNot('user_id', me()->id)->get();
                $messageContentLanguage = '';

                if($messageContent) {
                    $messageContentLanguage = detect_text_language($messageContent);
                }

                $messageInsertData = [
                    'content' => e((string) $messageContent),
                    'user_id' => me()->id,
                    'chat_uuid' => $chatId,
                    'participant_id' => $participantData->id,
                    'text_language' => $messageContentLanguage
                ];

                if($parentId) {
                    $replayableMessageExists = $chatData->messages()->where('id', $parentId)->exists();

                    if(empty($replayableMessageExists)) {
                        return $this->responseResourceNotFoundError('Message', $parentId);
                    }

                    $messageInsertData['parent_id'] = $parentId;
                }

                try {
                    $messageData = DB::transaction(function () use ($chatData, $messageInsertData, $attachmentIds) {
                        $messageData = $chatData->messages()->create($messageInsertData);

                        if ($attachmentIds->isNotEmpty()) {
                            $attachments = MessageAttachment::query()
                                ->whereIn('id', $attachmentIds)
                                ->where('user_id', me()->id)
                                ->whereNull('message_id')
                                ->get();

                            if ($attachments->count() !== $attachmentIds->count()) {
                                throw new Exception('Invalid attachments.');
                            }

                            MessageAttachment::whereIn('id', $attachments->pluck('id'))
                                ->update(['message_id' => $messageData->id]);
                        }

                        return $messageData;
                    });
                } catch (Exception $e) {
                    return $this->responseValidationError([
                        'message' => $e->getMessage(),
                        'errors' => [
                            'attachments' => [$e->getMessage()]
                        ]
                    ]);
                }

                $participantData->update([
                    'last_read_message_id' => $messageData->id,
                    'last_read_at' => now()
                ]);

                try {
                    event(new MessageReceivedEvent($messageData));

                    $otherParticipants->each(function ($participantData) {
                        $participantData->user->notify(new MessageReceivedNotification());
                    });
                } catch (Throwable $th) {
                    // Pass
                }

                $chatData->update([
                    'last_activity' => now()
                ]);

                if ($chatData->type->isDirect()) {
                    HiddenChat::where('chat_id', $chatData->id)->delete();
                }

                $this->dispatchBotReplyIfMentioned((string) $messageContent, $chatData->chat_id, me()->id, $parentId);

                return $this->responseSuccess([
                    'data' => null
                ]);
            }
    
            return $this->responseResourceNotFoundError('Chat', $chatId);
        }
        else{
            return $this->throwValidationError($validator);
        }
    }

    public function launcherSendMessage(Request $request)
    {
        $validator = Validator::make([
            'chat_id' => $request->get('chat_id'),
            'content' => $request->get('content'),
            'payload' => $request->get('payload')
        ], [
            'chat_id' => ['required', 'uuid'],
            'content' => ['required', 'string', 'min:1', XRule::join('max', config('chat.message.validation.content.max'))],
            'payload' => ['nullable', 'array']
        ]);

        if($validator->passes()) {
            $chatId = $request->get('chat_id');
            $messageContent = $request->get('content');
            $payload = $request->get('payload');

            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                if ($chatData->type->isDirect()) {
                    $interlocutor = $chatData->participants()->whereNot('user_id', me()->id)->first();
                    if ($interlocutor && $interlocutor->user && me()->isBlockedWith($interlocutor->user)) {
                        return $this->responseError([
                            'message' => 'You cannot message this user.',
                            'errors' => [
                                'chat_id' => ['You cannot message this user.']
                            ]
                        ], 403);
                    }
                }

                $participantData = $chatData->participants()->where('user_id', me()->id)->first();

                $messageInsertData = [
                    'content' => e($messageContent),
                    'user_id' => me()->id,
                    'chat_uuid' => $chatId,
                    'participant_id' => $participantData->id
                ];

                $messageData = $chatData->messages()->create($messageInsertData);

                $chatData->update([
                    'last_activity' => now()
                ]);

                try {
                    event(new MessageReceivedEvent($messageData));
                } catch (Throwable $th) {
                    // Pass
                }

                if ($chatData->type->isDirect()) {
                    HiddenChat::where('chat_id', $chatData->id)->delete();
                }

                $this->dispatchBotReplyIfMentioned($messageContent, $chatData->chat_id, me()->id, null);

                if($payload) {
                    $resourceId = data_get_integer($payload, 'id', 0);
                    $resourceType = data_get($payload, 'type', null);

                    if($resourceId) {
                        if($resourceType == 'product') {
                            try {
                                $productData = Product::listable()->find($resourceId);
    
                                $messageData->linkSnapshot()->create([
                                    'title' => Str::limit($productData->title, 250),
                                    'description' => Str::limit($productData->description, 250),
                                    'url' => $productData->url,
                                    'metadata' => [
                                        'is_fallback' => false,
                                        'preview_image_base64' => null
                                    ]
                                ]);
                            } catch (Exception $e) {
                                // Pass
                            }
                        }

                        else if($resourceType == 'job') {
                            try {
                                $jobData = JobListing::listable()->find($resourceId);
                                $jobData->increment('applications_count');
                                $jobData->increment('views_count');

                                $messageData->linkSnapshot()->create([
                                    'title' => Str::limit($jobData->title, 250),
                                    'description' => Str::limit($jobData->overview, 250),
                                    'url' => $jobData->url,
                                    'metadata' => [
                                        'is_fallback' => false,
                                        'preview_image_base64' => null
                                    ]
                                ]);
                            } catch (Exception $e) {
                                // Pass
                            }
                        }
                    }
                }

                return $this->responseSuccess([
                    'data' => null
                ]);
            }
    
            return $this->responseResourceNotFoundError('Chat', $chatId);
        }
        else{
            return $this->throwValidationError($validator);
        }
    }

    private function dispatchBotReplyIfMentioned(string $content, string $chatUuid, int $senderUserId, ?int $parentId): void
    {
        $botHandle = config('constants.BOT_HANDLE');
        $botUsername = ltrim((string) $botHandle, '@');

        if (
            $this->containsBotHandle($content, $botHandle) &&
            strtolower((string) me()->username) !== strtolower($botUsername)
        ) {
            ProcessAiChatReply::dispatch($content, $senderUserId, $chatUuid, $parentId);
        }
    }

    private function containsBotHandle(string $text, ?string $botHandle): bool
    {
        $handle = trim((string) $botHandle);
        if ($handle === '' || $text === '') {
            return false;
        }

        $escaped = preg_quote($handle, '/');
        return (bool) preg_match('/(?<![A-Za-z0-9_])' . $escaped . '(?![A-Za-z0-9_])/iu', $text);
    }

    public function addReaction(Request $request, ReactionService $reactionService)
    {
        $request->validate([
            'message_id' => ['required', 'integer'],
            'unified_id' => ['required', 'string', 'min:4', 'max:32']
        ]);

        $reactionUnifiedId = $request->get('unified_id');
        $messageId = $request->get('message_id');

        try {
            $messageData = Message::find($messageId);

            if ($messageData) {
                $isReactionAdded = $reactionService
                    ->setUserId(me()->id)
                    ->setReactable($messageData)
                    ->setUnifiable(strtolower($reactionUnifiedId))
                    ->handleReaction();
    
                return $this->responseSuccess([
                    'data' => ReactionCollection::make($messageData->reactions)
                ]);
            }

            return $this->responseResourceNotFoundError('Message', $messageId);
        }
        
        catch (Exception $e) {
            return $this->responseError([
                'message' => $e->getMessage(),
                'errors' => [
                    $e->getMessage()
                ]
            ]);
        }
    }

    public function deleteMessage(Request $request)
    {
        $request->validate([
            'message_id' => ['required', 'integer']
        ]);

        $messageId = $request->get('message_id');

        $chatData = Chat::participatedChats()->whereHas('messages', function ($query) use ($messageId) {
            $query->where('id', $messageId);
        })->first();

        if(! empty($chatData)) {
            $messageData = $chatData->messages()->find($messageId);
        }

        if ($messageData) {
            try {
                $payload = $request->array('payload', []);
                $isGlobalDelete = ($messageData->isSender() && empty($payload['delete_for_all']) != true);

                if($isGlobalDelete) {
                    (new MessageGlobalDeleteAction($messageData))->execute();

                    event(new MessageDeletedEvent($messageData));
                }
                else {
                    (new MessagesLocalDeleteAction(new Collection([$messageData])))->execute();
                }

                $messageData->linkSnapshot()->delete();

                return $this->responseSuccess([
                    'data' => [
                        'is_global_delete' => $isGlobalDelete
                    ]
                ]);
            } 
            catch (Throwable $th) {
                return $this->responseError([
                    'message' => $th->getMessage(),
                    'errors' => [
                        $th->getMessage()
                    ]
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Message', $messageId);
    }

    public function clearConversation(string $chatId) {
        if(Str::isUuid($chatId)) {
            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                $batchSize = 1000;
                
                $chatData->messages()->excludeDeleted()->chunk($batchSize, function ($messagesChunkList) {
                    (new MessagesLocalDeleteAction($messagesChunkList))->execute();
                });

                return $this->responseSuccess([
                    'data' => null
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Chat', $chatId); 
    }

    public function archiveChat(string $chatId) {
        if(Str::isUuid($chatId)) {
            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                if(! $chatData->isArchived(me()->id)) {
                    $chatData->archiveChat(me()->id);
                }

                return $this->responseSuccess([
                    'data' => null
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Chat', $chatId);
    }

    public function unarchiveChat(string $chatId) {
        if(Str::isUuid($chatId)) {
            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                $chatData->unarchiveChat(me()->id);

                return $this->responseSuccess([
                    'data' => null
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Chat', $chatId);
    }

    public function deleteChat(string $chatId) {
        if(Str::isUuid($chatId)) {
            $chatData = Chat::participatedChats()->where('chat_id', $chatId)->first();

            if($chatData) {
                $batchSize = 1000;
                
                $chatData->messages()->excludeDeleted()->chunk($batchSize, function ($messagesChunkList) {
                    (new MessagesLocalDeleteAction($messagesChunkList))->execute();
                });

                HiddenChat::create([
                    'chat_id' => $chatData->id,
                    'user_id' => me()->id,
                    'type' => $chatData->type
                ]);

                return $this->responseSuccess([
                    'data' => null
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Chat', $chatId);
    }

    private function initiateChat(int $userId)
    {
        $chatData = Chat::where('type', ChatType::DIRECT);
        $participantIds = [me()->id, $userId];

        foreach ($participantIds as $id) {
            $chatData->whereHas('participants', function ($query) use ($id) {
                $query->where('user_id', $id);
            });
        }

        $chatData = $chatData->first();

        if(empty($chatData)) {
            $chatData = Chat::create([
                'chat_id' => Str::uuid(),
                'type' => ChatType::DIRECT,
                'created_at' => now(),
                'last_activity' => null
            ]);

            
            $chatData->addParticipant(me()->id);
            $chatData->addParticipant($userId);
        }

        return $chatData;
    }
}
