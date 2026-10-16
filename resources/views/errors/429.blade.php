@extends('errors.minimal')

@section('title', lexicon('label_common_Error429Title', 'Too Many Requests'))
@section('code', '429')
@section('message', lexicon('msg_common_Error429Message', 'Too Many Requests'))