@extends('errors.minimal')

@section('title', lexicon('label_common_Error401Title', 'Unauthorised'))
@section('code', '401')
@section('message', lexicon('msg_common_Error401Message', 'Unauthorised'))