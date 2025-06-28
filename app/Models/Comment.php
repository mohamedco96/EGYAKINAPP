<?php

namespace App\Models;

// Alias for backward compatibility - extends the modular Comment model
class Comment extends \App\Modules\Comments\Models\Comment
{
    // This class serves as a backward compatibility layer
    // All functionality is inherited from the modular Comment model
}
