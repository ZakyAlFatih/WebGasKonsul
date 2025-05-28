<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Carbon\Carbon;

class RegisterController extends Controller
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
        $this->firestore = app('firebase.firestore');
    }

    public function showCounselorRegisterForm()
    {
        return view('register_counselor');
    }

    public function showUserRegisterForm()
    {
        return view('register_user');
    }

    public function storeCounselor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'bidang' => 'required|string|max:255',
            'license' => 'nullable|string|max:255',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $userRecord = $this->auth->createUserWithEmailAndPassword(
                $request->email,
                $request->password
            );

            $uid = $userRecord->uid;

            $firestoreRef = $this->firestore->database()->collection('counselors')->document($uid);

            $firestoreRef->set([
                'uid' => $uid,
                'name' => $request->name,
                'email' => $request->email,
                'bidang' => $request->bidang,
                'license' => $request->license,
                'role' => 'counselor',
                'createdAt' => Carbon::now()->toDateTimeString(),
                'rate' => 0,
                'rating' => [],
            ]);

            // Hapus session login otomatis jika ada, agar user harus login
            Session::forget('uid');
            Session::forget('isCounselor');

            // Redirect ke halaman login dengan pesan sukses
            return redirect()->route('login')->with('success', 'Pendaftaran counselor berhasil! Silakan login.');

        } catch (AuthException $e) {
            return redirect()->back()->withInput()->withErrors(['firebase' => $e->getMessage()]);
        } catch (FirebaseException $e) {
            return redirect()->back()->withInput()->withErrors(['firebase' => $e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors(['general' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $userRecord = $this->auth->createUserWithEmailAndPassword(
                $request->email,
                $request->password
            );

            $uid = $userRecord->uid;

            $firestoreRef = $this->firestore->database()->collection('users')->document($uid);

            $firestoreRef->set([
                'uid' => $uid,
                'name' => $request->name,
                'email' => $request->email,
                'role' => 'user',
                'avatar' => null,
                'createdAt' => Carbon::now()->toDateTimeString(),
            ]);

            // Hapus session login otomatis jika ada, agar user harus login
            Session::forget('uid');
            Session::forget('isCounselor');

            // Redirect ke halaman login dengan pesan sukses
            return redirect()->route('login')->with('success', 'Pendaftaran user berhasil! Silakan login.');

        } catch (AuthException $e) {
            return redirect()->back()->withInput()->withErrors(['firebase' => $e->getMessage()]);
        } catch (FirebaseException $e) {
            return redirect()->back()->withInput()->withErrors(['firebase' => $e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors(['general' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }
}