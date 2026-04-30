<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for the DirectChat module.
 *
 * 1. messages.deleted_at
 *    Every message query applies the SoftDeletes global scope (WHERE deleted_at IS NULL).
 *    Without an index, MySQL must scan all rows on each query.
 *
 * 2. messages composite (conversation_id, deleted_at, created_at)
 *    The unread-count batch JOIN query filters by conversation_id, deleted_at IS NULL,
 *    and created_at > last_read_at. A composite index covering all three columns
 *    lets MySQL satisfy the WHERE entirely from the index without hitting the table.
 *
 * 3. conversation_participants composite (user_id, conversation_id)
 *    The unread-count JOIN joins on cp.conversation_id AND cp.user_id = ?.
 *    The existing index('user_id') is single-column; the existing unique(conversation_id, user_id)
 *    has conversation_id first so it can't be used for user_id-first lookups.
 *    A (user_id, conversation_id) composite covers both the JOIN condition and the forUser scope.
 *
 * 4. message_reactions.user_id
 *    No index exists. Needed for cascade deletes, user-scoped queries, and eager-load joins.
 */
return new class extends Migration
{
    public function up(): void
    {
        // messages: index on deleted_at alone (helps the global scope filter)
        Schema::table('messages', function (Blueprint $table) {
            $table->index('deleted_at', 'messages_deleted_at_index');

            // Composite covering the unread-count query:
            // WHERE conversation_id = ? AND deleted_at IS NULL AND created_at > ?
            $table->index(
                ['conversation_id', 'deleted_at', 'created_at'],
                'messages_conv_deleted_created_index'
            );
        });

        // conversation_participants: composite (user_id, conversation_id) for JOIN lookups
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->index(
                ['user_id', 'conversation_id'],
                'cp_user_conversation_index'
            );
        });

        // message_reactions: index on user_id
        Schema::table('message_reactions', function (Blueprint $table) {
            $table->index('user_id', 'message_reactions_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_deleted_at_index');
            $table->dropIndex('messages_conv_deleted_created_index');
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropIndex('cp_user_conversation_index');
        });

        Schema::table('message_reactions', function (Blueprint $table) {
            $table->dropIndex('message_reactions_user_id_index');
        });
    }
};
