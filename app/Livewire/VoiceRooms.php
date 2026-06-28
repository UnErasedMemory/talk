<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\Room;
use Livewire\Attributes\Layout;

class VoiceRooms extends Component
{
    public $rooms;
    public $selectedRoomId = null;
    public $hasGrantedAccess = false;

    public function mount()
    {
        $this->rooms = Room::all();
    }

    public function grantAccess()
    {
        $this->hasGrantedAccess = true;
    }

    public function selectRoom($id)
    {
        $this->selectedRoomId = $id;
        $this->dispatch('room-selected', roomId: $id);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.voice-rooms');
    }
}
