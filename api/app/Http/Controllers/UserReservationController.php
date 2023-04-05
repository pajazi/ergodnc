<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(!auth()->user()->tokenCan('reservations.show'), Response::HTTP_FORBIDDEN);

        validator(request()->all(), [
            'status' => Rule::in(Reservation::STATUS_ACTIVE, Reservation::STATUS_CANCELLED),
            'office_id' => ['integer'],
            'from_date' => ['date', 'required_with:to_date'],
            'to_date' => ['date', 'required_with:from_date', 'after:from_date']
        ])->validate();

        $reservations = Reservation::query()
            ->where('user_id', auth()->id())
            ->when(request('office_id'),
                fn($query) => $query->where('office_id', request('office_id'))
            )
            ->when(request('status'),
                fn($query) => $query->where('status', request('status'))
            )
            ->when(request('from_data') && request('to_date'),
                fn($query) => $query->where(function ($query) {
                    $query->whereBetween('start_date', [request('from_data'), request('to_date')])
                        ->orWhereBetween('end_date', [request('from_data'), request('to_date')]);
                }))
            ->with(['office'])
            ->paginate(20);

        return ReservationResource::collection($reservations);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reservation $reservation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        //
    }
}
