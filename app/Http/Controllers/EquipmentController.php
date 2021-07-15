<?php

namespace App\Http\Controllers;

use App\Equipment;
use App\Notifications\InvoicePaid;
use App\Notifications\NotificationAdm;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use \Illuminate\Support\Facades\Validator;

class EquipmentController extends Controller
{
    public function new(Request $request)
    {
        $token = $request->bearerToken(); # Получаем токен из заголовка

        $user = User::where('api_token', $token)->get(); # Запрос в БД по условию

        if ($user->count()) {
            if (!$user[0]->isAdmin) {
                # Валидация
                $validate = Validator::make($request->all(), [
                    'title' => 'required|string',
                    'price' => 'required|integer',
                    'serial_number' => 'required|string',
                    'inventory_number' => 'required|string'
                ]);

                # Если произошла ошибка во время валидации
                if ($validate->fails()) {
                    $response = [
                        'type' => 'error',
                        'msg' => $validate->errors(),
                    ];
                    Log::error('Validate Error. Bearer Token: ' . $token . '; IP: ' . $request->ip());
                    return response()->json($response, 400, [], JSON_UNESCAPED_UNICODE); # Возвращаем ошибку со статусом 400
                } else {
                    # Добавляем запись в БД
                    Equipment::create([
                        'title' => $request->title,
                        'price' => $request->price,
                        'serial_number' => $request->serial_number,
                        'inventory_number' => $request->inventory_number,
                        'user_id' => $user[0]->id,
                    ]);

                    $response = [
                        'type' => 'success',
                        'msg' => 'Успешно'
                    ];

                    # Отправка почты на MailTrap
                    Notification::route('mail', 'eecii@list.ru')
                        ->route('title', 'Зарегистрировано новое оборудование')
                        ->route('title_equipment', $request->title)
                        ->route('price_equipment', $request->price)
                        ->route('serial_number_equipment', $request->serial_number)
                        ->route('inventory_number_equipment', $request->inventory_number)
                        ->route('user_login', $user[0]->login)
                        ->notify(new NotificationAdm());
                    Log::info('Success. Bearer Token: ' . $token . '; IP: ' . $request->ip());

                    $notification_data = [
                        'title' => $request->title,
                        'price' => $request->price,
                        'serial_number' => $request->serial_number,
                        'inventory_number' => $request->inventory_number,
                        'user_id' => $user[0]->id,
                    ];
                    Log::info('New Equipment.', $notification_data);
                    return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE);
                }
            } else {
                $response = [
                    'type' => 'error',
                    'msg' => 'Нет доступа'
                ];

                Log::error('Access Denied. Bearer Token: ' . $token . '; IP: ' . $request->ip());
                return response()->json($response, 403, [], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $response = [
                'type' => 'error',
                'msg' => 'Неизвестный пользователь'
            ];
            Log::error('Unknown User. Bearer Token: ' . $token . '; IP: ' . $request->ip());
            return response()->json($response, 401, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function update(Request $request)
    {
        $token = $request->bearerToken();

        $user = User::where('api_token', $token)->get();

        if ($user->count()) {
            if ($user[0]->isAdmin) {
                $validate = Validator::make($request->all(), [
                    'id' => 'required|integer|exists:equipment',
                    'warehouse' => 'required|integer',
                ]);

                if ($validate->fails()) {
                    $response = [
                        'type' => 'error',
                        'msg' => $validate->errors(),
                    ];
                    Log::error('Validate Error. Bearer Token: ' . $token . '; IP: ' . $request->ip());
                    return response()->json($response, 400, [], JSON_UNESCAPED_UNICODE);
                } else {
                    $equipmentInfo = Equipment::where('id', $request->id)->get();
                    $usersInfo = User::where('id', $equipmentInfo[0]->user_id)->get();


                    Equipment::where('id', $request->id)->update(['warehouse_id' => $request->warehouse]); # Обновляем запись в БД

                    $response = [
                        'type' => 'success',
                        'msg' => 'Успешно'
                    ];
                    Log::info('Success. Bearer Token: ' . $token . '; IP: ' . $request->ip());

                    $notification_data = [
                        'id' => $request->id,
                        'warehouse_id' => $request->warehouse,
                    ];
                    Log::info('Update Equipment.', $notification_data);
                    # Отправка почты на MailTrap
                    Notification::route('mail', $usersInfo[0]->email)
                        ->route('title', 'Обновления оборудования')
                        ->route('text', 'Ваше оборудование перемещено на склад #'.$request->warehouse)
                        ->notify(new InvoicePaid());
                    return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE);
                }
            } else {
                $response = [
                    'type' => 'error',
                    'msg' => 'Нет доступа'
                ];
                Log::error('Access Denied. Bearer Token: ' . $token . '; IP: ' . $request->ip());
                return response()->json($response, 403, [], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $response = [
                'type' => 'error',
                'msg' => 'Неизвестный пользователь'
            ];
            Log::error('Unknown User. Bearer Token: ' . $token . '; IP: ' . $request->ip());
            return response()->json($response, 401, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function select(Request $request)
    {
        $token = $request->bearerToken();

        $user = User::where('api_token', $token)->get();

        if ($user->count()) {
            $validate = Validator::make($request->all(), [
                'filter' => 'string|nullable',
                'asc' => 'boolean',
                'q' => 'nullable',
                'type_search' => 'string|nullable',
            ]);
            if ($user[0]->isAdmin) {
                if ($validate->fails()) {
                    $response = [
                        'type' => 'error',
                        'msg' => $validate->errors(),
                    ];
                    Log::error('Validate Error. Bearer Token: ' . $token . '; IP: ' . $request->ip());
                    return response()->json($response, 400, [], JSON_UNESCAPED_UNICODE);
                } else {
                    if ($request->filter == 'date') { # Фильтрация по дате
                        if ($request->type_search == 'title') {
                            if ($request->asc) $equipment = Equipment::where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'serial_number') {
                            if ($request->asc) $equipment = Equipment::where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'inventory_number') {
                            if ($request->asc) $equipment = Equipment::where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'DESC')
                                ->get();
                        } else {
                            if ($request->asc) $equipment = Equipment::orderBy('created_at', 'ASC')->get();
                            else $equipment = Equipment::orderBy('created_at', 'DESC')->get();
                        }
                    } elseif ($request->filter == 'price') { # Фильтрация по стоимости
                        if ($request->type_search == 'title') {
                            if ($request->asc) $equipment = Equipment::where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'serial_number') {
                            if ($request->asc) $equipment = Equipment::where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'inventory_number') {
                            if ($request->asc) $equipment = Equipment::where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'DESC')
                                ->get();
                        } else {
                            if ($request->asc) $equipment = Equipment::orderBy('price', 'ASC')->get();
                            else $equipment = Equipment::orderBy('price', 'DESC')->get();
                        }
                    } elseif ($request->filter == 'status') { # Фильтрация по статусу
                        if ($request->asc) $equipment = Equipment::orderBy('warehouse_id', 'ASC')->get();
                        else $equipment = Equipment::orderBy('warehouse_id', 'DESC')->get();
                    } else {
                        $equipment = Equipment::all();
                    }

                    $items = [];

                    foreach ($equipment as $item) {
                        $data = [
                            'title' => $item->title,
                            'price' => $item->price,
                            'serial_number' => $item->serial_number,
                            'inventory_number' => $item->inventory_number,
                            'created_at' => $item->created_at->format('d.m.Y H:i'),
                            'updated_at' => $item->updated_at->format('d.m.Y H:i'),
                            'warehouse_id' => $item->warehouse_id,
                        ];
                        array_push($items, $data);
                    }

                    $response = [
                        'response' => $items
                    ];
                    Log::info('Success. Bearer Token: ' . $token . '; IP: ' . $request->ip());
                    return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE);
                }
            } else {
                if ($validate->fails()) {
                    $response = [
                        'type' => 'error',
                        'msg' => $validate->errors(),
                    ];
                    Log::error('Validate Error. Bearer Token: ' . $token . '; IP: ' . $request->ip());
                    return response()->json($response, 400, [], JSON_UNESCAPED_UNICODE);
                } else {
                    if ($request->filter == 'date') { # Фильтрация по дате
                        if ($request->type_search == 'title') {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'serial_number') {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'inventory_number') {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('created_at', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'status') {
                            if ($request->asc) {
                                if ($request->q == 0) $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNull('warehouse_id')
                                    ->orderBy('created_at', 'ASC')
                                    ->get();
                                else $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNotNull('warehouse_id')
                                    ->orderBy('created_at', 'ASC')
                                    ->get();
                            } else {
                                if ($request->q == 0) $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNull('warehouse_id')
                                    ->orderBy('created_at', 'DESC')
                                    ->get();
                                else $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNotNull('warehouse_id')
                                    ->orderBy('created_at', 'DESC')
                                    ->get();
                            }
                        } else {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->orderBy('created_at', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->orderBy('created_at', 'DESC')
                                ->get();
                        }
                    } elseif ($request->filter == 'price') { # Фильтрация по стоимости
                        if ($request->type_search == 'title') {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'serial_number') {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'inventory_number') {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('price', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'status') {
                            if ($request->asc) {
                                if ($request->q == 0) $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNull('warehouse_id')
                                    ->orderBy('price', 'ASC')
                                    ->get();
                                else $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNotNull('warehouse_id')
                                    ->orderBy('price', 'ASC')
                                    ->get();
                            } else {
                                if ($request->q == 0) $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNull('warehouse_id')
                                    ->orderBy('price', 'DESC')
                                    ->get();
                                else $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNotNull('warehouse_id')
                                    ->orderBy('price', 'DESC')
                                    ->get();
                            }
                        } else {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->orderBy('price', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->orderBy('price', 'DESC')
                                ->get();
                        }
                    } elseif ($request->filter == 'status') { # Фильтрация по статусу
                        if ($request->type_search == 'title') {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('warehouse_id', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('title', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('warehouse_id', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'serial_number') {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('warehouse_id', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('serial_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('warehouse_id', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'inventory_number') {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('warehouse_id', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->where('inventory_number', 'LIKE', '%' . $request->q . '%')
                                ->orderBy('warehouse_id', 'DESC')
                                ->get();
                        } elseif ($request->type_search == 'status') {
                            if ($request->asc) {
                                if ($request->q == 0) $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNull('warehouse_id')
                                    ->orderBy('warehouse_id', 'ASC')
                                    ->get();
                                else $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNotNull('warehouse_id')
                                    ->orderBy('warehouse_id', 'ASC')
                                    ->get();
                            } else {
                                if ($request->q == 0) $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNull('warehouse_id')
                                    ->orderBy('warehouse_id', 'DESC')
                                    ->get();
                                else $equipment = Equipment::where('user_id', $user[0]->id)
                                    ->whereNotNull('warehouse_id')
                                    ->orderBy('warehouse_id', 'DESC')
                                    ->get();
                            }
                        } else {
                            if ($request->asc) $equipment = Equipment::where('user_id', $user[0]->id)
                                ->orderBy('warehouse_id', 'ASC')
                                ->get();
                            else $equipment = Equipment::where('user_id', $user[0]->id)
                                ->orderBy('warehouse_id', 'DESC')
                                ->get();
                        }
                    } else {
                        $equipment = Equipment::all();
                    }

                    $items = [];

                    foreach ($equipment as $item) {
                        if ($item->warehouse_id == null) $status = 0;
                        else $status = 1;
                        $data = [
                            'title' => $item->title,
                            'price' => $item->price,
                            'serial_number' => $item->serial_number,
                            'inventory_number' => $item->inventory_number,
                            'created_at' => $item->created_at->format('d.m.Y H:i'),
                            'status' => $status,
                        ];
                        array_push($items, $data);
                    }

                    $response = [
                        'response' => $items
                    ];
                    Log::info('Success. Bearer Token: ' . $token . '; IP: ' . $request->ip());
                    return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE);
                }
            }
        } else {
            $response = [
                'type' => 'error',
                'msg' => 'Неизвестный пользователь'
            ];
            Log::error('Unknown User. Bearer Token: ' . $token . '; IP: ' . $request->ip());
            return response()->json($response, 401, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
