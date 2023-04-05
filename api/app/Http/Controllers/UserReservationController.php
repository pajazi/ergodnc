<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReservationResource;
use App\Models\Office;
use App\Models\Reservation;
use App\Notifications\NewReservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
                fn($query) => $query->betweenDates(request('from_date'), request('to_date')))
            ->with(['office'])
            ->paginate(20);

        return ReservationResource::collection($reservations);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(!auth()->user()->tokenCan('reservations.make'), Response::HTTP_FORBIDDEN);

        validator(request()->all(), [
            'office_id' => ['required', 'integer'],
            'start_date' => ['required', 'data:Y-m-d', 'after'.now()->addDay()->toDateString()],
            'end_date' => ['required', 'data:Y-m-d', 'after:start_date']
        ]);

        try {
            $office = Office::findOrFail(request('office_id'));
        } catch (ModelNotFoundException $e) {
            throw ValidationException::withMessages(['office_id' => 'Invalid office_id']);
        }

        if ($office->user_id === auth()->id()) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on your own office']);
        }

        $reservation = Cache::lock('reservations_office_'.$office->id, 10)->block(3, function () use ($office) {
            $numberOfDays = Carbon::parse(request('end_date'))->endOfDay()->diffInDays(
                Carbon::parse(request('start_date'))->startOfDay()
            ) + 1;

            if ($office->reservations()->activeBetween(request('start_date'), request('end_date'))->exists()) {
                throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation during this period.']);
            }

            $price = $numberOfDays * $office->price_per_day;

            if ($numberOfDays >= 28 && $office->monthly_discount) {
                $price = $price - ($price * $office->monthly_discount / 100);
            }

            return Reservation::create([
                'user_id' => auth()->id(),
                'office_id' => $office->id,
                'start_date' => \request('start_date'),
                'end_date' => request('end_date'),
                'status' => Reservation::STATUS_ACTIVE,
                'price' => $price
            ]);
        });

        Notification::send(auth()->user(), new NewReservation($reservation));

        return ReservationResource::make($reservation->load('office'));
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
